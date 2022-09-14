<?php

namespace App\Discord\SlashCommands;

use App\VoiceChannel;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;
use Exception;

class VoiceChannelDeleteSlashCommand implements SlashCommandListenerInterface
{
    public const VOICE_CHANNEL_DELETE = 'voicedelete';

    /**
     * @throws Exception
     */
    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::VOICE_CHANNEL_DELETE)) {
            return;
        }

        $id = $interaction->data->options['id'] ? $interaction->data->options['id']->value : $interaction->channel_id;

        $vc = VoiceChannel::where('vc_discord_id', $id)->first();
        if ($interaction->member->user->id !== $vc->owner) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Ти не можеш видалити голосовий чат, тому що ти не є його власником.'), true);
            $interaction->acknowledge();
            return;
        }

        $interaction->guild->channels->delete($id)->done(function () use ($interaction, $vc) {
            $vc->delete();
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Чат було успішно видалено!'), true);
            $interaction->acknowledge();
        });
    }
}
