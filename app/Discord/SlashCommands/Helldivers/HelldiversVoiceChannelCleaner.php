<?php

namespace App\Discord\SlashCommands\Helldivers;

use App\Discord\SlashCommands\HelldiversSlashCommand;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\HelldiversLfgVoiceChannel;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Exception;
use Illuminate\Support\Facades\Log;

class HelldiversVoiceChannelCleaner
{
    private const CHECK_INTERVAL = 60; // Check every minute
    public const DEFAULT_EMPTY_TIMEOUT = 600; // Default 10 minutes in seconds

    private static function getEmptyTimeout(string $guildId): int
    {
        $settings = SettingsObject::getFromGuildId($guildId);
        return ($settings->helldivers->emptyVcTimeout ?? self::DEFAULT_EMPTY_TIMEOUT);
    }

    public static function startEmptyChannelChecker(Discord $discord): void
    {
        $discord->getLoop()->addPeriodicTimer(self::CHECK_INTERVAL, function () use ($discord) {
            Log::info(class_basename(static::class) . ': Checking for empty voice channels...');
            self::checkEmptyChannels($discord);
        });
    }

    private static function checkEmptyChannels(Discord $discord): void
    {
        // Get all empty voice channels grouped by guild
        $vcs = HelldiversLfgVoiceChannel::query()
            ->whereRaw("participants = '{\"1\":\"\",\"2\":\"\",\"3\":\"\",\"4\":\"\"}'")
            ->get()
            ->groupBy('guild_id');

        Log::info(class_basename(static::class) . ': Found ' . count($vcs) . ' empty voice channels.');

        foreach ($vcs as $guildId => $guildVcs) {
            Log::info(class_basename(static::class) . ": guild[$guildId]: Checking guild for empty voice channels.");
            $timeout = self::getEmptyTimeout($guildId);
            Log::info(class_basename(static::class) . ": guild[$guildId]: Empty voice channel timeout is $timeout seconds.");

            foreach ($guildVcs as $vc) {
                if ($vc->updated_at->addSeconds($timeout)->isPast()) {
                    try {
                        $guild = $discord->guilds->get('id', $vc->guild_id);
                        if (!$guild) {
                            continue;
                        }
                        Log::info(class_basename(static::class) . ": guild[$guildId]: Start to delete empty channel[$vc->vc_discord_id].");
                        $guild->channels->delete($vc->vc_discord_id)->otherwise(function (Exception $e) use ($vc) {
                            $vc->delete();

                            Log::error(class_basename(static::class) . ': Failed to delete empty channel', [
                                'channel_id' => $vc->vc_discord_id,
                                'guild_id' => $vc->guild_id,
                                'error' => $e->getMessage()
                            ]);
                        })->done(function () use ($vc, $guild) {
                            $vc->delete();

                            $guild->channels->fetch($vc->lfg_channel_id)->then(function (Channel $channel) use ($vc) {
                                $message = $channel->messages->get('id', $vc->lfg_message_id);

                                $tagMessageId = $vc->tag_message_id;
                                if (!empty($tagMessageId)) {
                                    $channel->messages->fetch($tagMessageId)->then(function (Message $tagMessage) use ($channel) {
                                        $channel->messages->delete($tagMessage);
                                    }, function (Exception $e) {
                                        Log::error(class_basename(static::class) . ': Failed to delete tag message', ['exception' => $e->getMessage()]);
                                    });
                                }

                                if (empty($message)) {
                                    $channel->messages->fetch($vc->lfg_message_id)->then(function (Message $message) {
                                        HelldiversSlashCommand::reRenderLfgEmbed($message);
                                    });
                                } else {
                                    HelldiversSlashCommand::reRenderLfgEmbed($message);
                                }
                            });
                        });
                    } catch (Exception $e) {
                        Log::error(class_basename(static::class) . ': Failed to process empty channel cleanup', [
                            'channel_id' => $vc->vc_discord_id,
                            'guild_id' => $guildId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
    }
}
