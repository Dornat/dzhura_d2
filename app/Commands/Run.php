<?php

namespace App\Commands;

use App\Discord\SlashCommands\Levels\LevelingSystem;
use App\Discord\SlashCommands\LevelsSlashCommand;
use App\Discord\SlashCommands\LfgDeleteSlashCommandListener;
use App\Discord\SlashCommands\LfgEditSlashCommand;
use App\Discord\SlashCommands\LfgSlashCommandListener;
use App\Discord\SlashCommands\SettingsSlashCommand;
use App\Discord\SlashCommands\VoiceChannelCreateSlashCommand;
use App\Discord\SlashCommands\VoiceChannelDeleteSlashCommand;
use App\Discord\SlashCommands\VoiceChannelEditSlashCommand;
use App\Discord\SlashCommands\ZavalaSlashCommand;
use App\Lfg;
use App\VoiceChannel;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Event;
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
//
//            $http = new HttpServer(function (ServerRequestInterface $request) {
//                return Response::plaintext(
//                    "Hello World!\n"
//                );
//            });
//
//            $socket = new SocketServer('127.0.0.1:8080');
//            $http->listen($socket);
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

        $discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
            SettingsSlashCommand::act($interaction, $discord);
            LfgSlashCommandListener::act($interaction, $discord);
            LfgDeleteSlashCommandListener::act($interaction, $discord);
            LfgEditSlashCommand::act($interaction, $discord);
            LevelsSlashCommand::act($interaction, $discord);
            VoiceChannelCreateSlashCommand::act($interaction, $discord);
            VoiceChannelEditSlashCommand::act($interaction, $discord);
            VoiceChannelDeleteSlashCommand::act($interaction, $discord);
            ZavalaSlashCommand::act($interaction, $discord);
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
