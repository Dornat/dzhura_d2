<?php

namespace App\Commands;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\Services\ServerLogsService;
use App\Discord\SlashCommands\Helldivers\HelldiversVoiceChannelCleaner;
use App\Discord\SlashCommands\HelldiversSlashCommand;
use App\Discord\SlashCommands\Levels\LevelingSystem;
use App\Discord\SlashCommands\LevelsSlashCommand;
use App\Discord\SlashCommands\LfgDeleteSlashCommandListener;
use App\Discord\SlashCommands\LfgEditSlashCommand;
use App\Discord\SlashCommands\LfgSlashCommandListener;
use App\Discord\SlashCommands\Settings\Factories\ServerLogsSettingsFactory;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\SettingsSlashCommand;
use App\Discord\SlashCommands\VoiceChannelCreateSlashCommand;
use App\Discord\SlashCommands\VoiceChannelDeleteSlashCommand;
use App\Discord\SlashCommands\VoiceChannelEditSlashCommand;
use App\Discord\SlashCommands\ZavalaSlashCommand;
use App\Lfg;
use App\VoiceChannel;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\Parts\Guild\AuditLog\Entry;
use Discord\Parts\Guild\Ban;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Activity;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class Run extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'The main command that runs the bot.';

    /**
     * Array for tracking user bans.
     */
    private array $userBans = [];

    /**
     * Execute the console command.
     *
     * @return void
     * @throws IntentException
     * @throws Exception
     */
    public function handle(): void
    {
        $discord = new Discord([
            'token' => env('DISCORD_TOKEN'),
            'loadAllMembers' => true,
            'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
        ]);

        $discord->on('ready', function (Discord $discord) {
            $botActivity = new Activity($discord, [
                'type' => Activity::TYPE_PLAYING,
                'name' => 'Ліквідація москалів. [v' . config('app.version') . ']'
            ]);
            $discord->updatePresence($botActivity);

            foreach (config('slash_commands') as $opts) {
                $slashCommand = new DiscordCommand($discord, $opts);
                $discord->application->commands->save($slashCommand);
            }

            HelldiversVoiceChannelCleaner::startEmptyChannelChecker($discord);
        });

        $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
            LevelingSystem::act($message, $discord);
        });

        $discord->on(Event::MESSAGE_DELETE, function ($message, Discord $discord) {
            $lfg = Lfg::where('discord_id', $message->id)->first();
            if (!empty($lfg)) {
                /** @var VoiceChannel $vc */
                $vc = $lfg->vc()->get()->first();
                if (!empty($vc)) {
                    $discord->guilds->get('id', $message->guild_id)->channels->delete($vc->vc_discord_id);
                }
                $lfg->delete();
            }
        });

        $discord->on(Event::MESSAGE_DELETE_BULK, function ($messages, Discord $discord) {
            if ($messages instanceof \stdClass) {
                $ids = [$messages->id];
            } else {
                $ids = array_column($messages, 'id');
            }
            $lfgs = Lfg::whereIn('discord_id', $ids)->get();
            if (!$lfgs->isEmpty()) {
                foreach ($lfgs as $lfg) {
                    $lfg->delete();
                }
            }
        });

        $discord->on(Event::CHANNEL_DELETE, function (Channel $channel, Discord $discord) {
            $vc = VoiceChannel::where('vc_discord_id', $channel->id)->first();
            if (!empty($vc)) {
                $vc->delete();
            }
        });

        $discord->on(Event::VOICE_STATE_UPDATE, function (VoiceStateUpdate $newState, Discord $discord, VoiceStateUpdate|null $oldState) {
            HelldiversSlashCommand::actOnHelldiversVCLeave($newState, $discord, $oldState);
            HelldiversSlashCommand::actOnHelldiversVCEnter($newState, $discord, $oldState);
        });

        $discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
            SettingsSlashCommand::act($interaction, $discord);
            LfgSlashCommandListener::act($interaction, $discord);
            LfgDeleteSlashCommandListener::act($interaction, $discord);
            LfgEditSlashCommand::act($interaction, $discord);
            HelldiversSlashCommand::act($interaction, $discord);
            LevelsSlashCommand::act($interaction, $discord);
            VoiceChannelCreateSlashCommand::act($interaction, $discord);
            VoiceChannelEditSlashCommand::act($interaction, $discord);
            VoiceChannelDeleteSlashCommand::act($interaction, $discord);
            ZavalaSlashCommand::act($interaction, $discord);
        });

        $discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) {
            $settings = SettingsObject::getFromGuildId($member->guild_id);
            if ($settings->serverLogs->active && !empty($settings->serverLogs->sendMessagesChannel)) {
                ServerLogsService::actOnGuildMemberAddEvent($member, $discord, $settings);
            }
        });

        $discord->on(Event::GUILD_BAN_ADD, function (Ban $ban, Discord $discord) {
            $i = 0;
            $this->userBans[$ban->user->id] = time();

            foreach ($this->userBans as $userId => $banTime) {
                if (time() - $banTime > 5) {
                    unset($this->userBans[$userId]);
                }
            }
        });


        $discord->on(Event::GUILD_MEMBER_REMOVE, function (Member $member, Discord $discord) {
            $discord->getLoop()->addTimer(3, function () use ($member, $discord) {
                $settings = SettingsObject::getFromGuildId($member->guild_id);
                if ($settings->serverLogs->active && !empty($settings->serverLogs->sendMessagesChannel)) {
                    $guild = $discord->guilds->get('id', $member->guild_id);

                    $guild->getAuditLog([
                        'action_type' => Entry::MEMBER_KICK
                    ])->done(function (AuditLog $auditLog) use ($member, $settings, $discord, $guild) {
                        foreach ($auditLog->audit_log_entries as $entry) {
                            if ($entry->target_id === $member->id && abs(SlashCommandHelper::getCreationDateFromSnowflake($entry->id)->getTimestamp() - time()) <= 5) {
                                $guild->members->fetch($entry->user_id)->done(function (Member $kickIssuer) use ($entry, $settings, $discord, $member) {
                                    ServerLogsService::actOnGuildMemberKickEvent($member, $discord, $settings, $kickIssuer, $entry->reason);
                                });
                                return;
                            }
                        }
                        if (isset($this->userBans[$member->id])) {
                            $guild->getAuditLog([
                                'action_type' => Entry::MEMBER_BAN_ADD
                            ])->done(function (AuditLog $auditLog) use ($member, $settings, $discord, $guild) {
                                foreach ($auditLog->audit_log_entries as $entry) {
                                    if ($entry->target_id === $member->id && abs(SlashCommandHelper::getCreationDateFromSnowflake($entry->id)->getTimestamp() - time()) <= 5) {
                                        $guild->members->fetch($entry->user_id)->done(function (Member $banIssuer) use ($entry, $settings, $discord, $member) {
                                            ServerLogsService::actOnGuildMemberBanEvent($member, $discord, $settings, $banIssuer, $entry->reason);
                                        });
                                        return;
                                    }
                                }
                            });
                            return;
                        }
                        ServerLogsService::actOnGuildMemberRemoveEvent($member, $discord, $settings);
                    });
                }
            });
        });

        $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $member, Discord $discord, ?Member $oldMember) {
            $settings = SettingsObject::getFromGuildId($member->guild_id);
            if ($settings->serverLogs->active && !empty($settings->serverLogs->sendMessagesChannel)) {
                ServerLogsService::actOnGuildMemberUpdateEvent($member, $discord, $oldMember, $settings);
            }
        });

        $discord->run();
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
