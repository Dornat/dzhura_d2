<?php

namespace App\Discord\SlashCommands;

use App\VoiceChannel;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Interaction;
use Exception;

class VoiceChannelCreateSlashCommand implements SlashCommandListenerInterface
{
    public const VOICE_CHANNEL_CREATE = 'voicecreate';

    /**
     * @throws Exception
     */
    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::VOICE_CHANNEL_CREATE)) {
            return;
        }

        $name = $interaction->data->options['name']->value;
        $userLimit = $interaction->data->options['user_limit']->value;
        $category = $interaction->data->options['category']?->value;
        $channelCategory = null;

        if (!is_null($category)) {
            $channelCategory = $interaction->guild->channels->find(function (Channel $channel) use ($category) {
                if ($channel->type === Channel::TYPE_CATEGORY && strtolower($channel->name) === strtolower($category)) {
                    return $channel;
                }
                return null;
            });
        }

        $newVc = $interaction->guild->channels->create([
            'name' => $name,
            'type' => Channel::TYPE_VOICE,
            'user_limit' => $userLimit,
            'parent_id' => $channelCategory?->id
        ]);

        $interaction->guild->channels->save($newVc)->done(function (Channel $channel) use ($interaction) {
            $newVc = VoiceChannel::create([
                'vc_discord_id' => $channel->id,
                'owner' => $interaction->member->user->id,
                'name' => $channel->name,
                'user_limit' => $channel->user_limit
            ]);
            $newVc->save();

            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Голосовий канал було успішно створено!'), true);
            $interaction->acknowledge();
        });
    }
}
