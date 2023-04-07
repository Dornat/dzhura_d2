<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\Settings\SettingsObject;
use App\Lfg;
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

        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);
        if (empty($settingsObject->vc->permittedRoles) && !$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Недостатньо дозволів для виконання операції. :thinking_face:'), true);
            return;
        }
        if (!empty($settingsObject->vc->permittedRoles) && !$interaction->member->permissions->administrator) {
            if (empty(array_intersect(array_column($settingsObject->vc->permittedRoles, 'id'), array_keys($interaction->member->roles->jsonSerialize())))) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Недостатньо дозволів для виконання операції. :thinking_face:'), true);
                return;
            }
        }

        $createdVcsForOwnerCount = VoiceChannel::where('owner', $interaction->member->id)?->get()?->count();
        if ((!empty($createdVcsForOwnerCount) || $createdVcsForOwnerCount === 0) && !$interaction->member->permissions->administrator) {
            if ($settingsObject->vc->channelLimit <= $createdVcsForOwnerCount) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Досягнуто ліміту кількості створення голосових каналів. :face_with_monocle:'), true);
                return;
            }
        }

        $name = $interaction->data->options['name']->value;
        $userLimit = (int)$interaction->data->options['user_limit']->value;
        $category = $interaction->data->options['category']?->value;
        $lfgId = $interaction->data->options['lfg_id']?->value;

        $lfg = null;
        if (!is_null($lfgId)) {
            $lfg = Lfg::find($lfgId);
            if (empty($lfg)) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Групи з таким ідентифікатором не існує.'), true);
                return;
            }
        }

        if (!empty($settingsObject->vc->defaultCategory) && is_null($category)) {
            $category = $settingsObject->vc->defaultCategory;
        }

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
            'user_limit' => max($userLimit, 1),
            'parent_id' => $channelCategory?->id
        ]);

        $interaction->guild->channels->save($newVc)->done(function (Channel $channel) use ($interaction, $lfg) {
            $newVc = new VoiceChannel([
                'guild_id' => $interaction->guild_id,
                'vc_discord_id' => $channel->id,
                'owner' => $interaction->member->user->id,
                'name' => $channel->name,
                'user_limit' => $channel->user_limit,
                'category' => $channel->parent_id
            ]);

            if (!is_null($lfg)) {
                $lfg->vc()->save($newVc);
            } else {
                $newVc->save();
            }

            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Голосовий канал було успішно створено!'), true);
            $interaction->acknowledge();
        });
    }
}
