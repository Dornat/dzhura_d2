<?php

namespace App\Discord\SlashCommands;

use App\VoiceChannel;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\InteractionType;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Interaction;
use Exception;

class VoiceChannelEditSlashCommand implements SlashCommandListenerInterface
{
    public const VOICE_CHANNEL_EDIT = 'voiceedit';
    public const VOICE_CHANNEL_EDIT_MODAL = 'voiceedit_modal';
    public const VOICE_CHANNEL_EDIT_MODAL_NAME = 'voiceedit_modal_name';
    public const VOICE_CHANNEL_EDIT_MODAL_USER_LIMIT = 'voiceedit_modal_user_limit';

    /**
     * @throws Exception
     */
    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::VOICE_CHANNEL_EDIT)) {
            return;
        }
        $id = $interaction->data->options['id'] ? $interaction->data->options['id']->value : $interaction->channel_id;
        /** @var VoiceChannel $vc */
        $vc = VoiceChannel::where('vc_discord_id', $id)->first();
        if (is_null($vc)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Неправильний ID голосового каналу. :face_with_monocle:'), true);
            return;
        }
        if ($interaction->member->user->id !== $vc->owner && !$interaction->member->permissions->administrator) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Ти не можеш редагувати голосовий канал, тому що ти не є його власником. :man_shrugging:'), true);
            return;
        }

        $nameInput = TextInput::new('Назва', TextInput::STYLE_SHORT, self::VOICE_CHANNEL_EDIT_MODAL_NAME)
            ->setValue($vc->name)
            ->setPlaceholder('Назва');
        $nameRow = ActionRow::new()->addComponent($nameInput);
        $userLimitInput = TextInput::new('Кількість учасників', TextInput::STYLE_SHORT, self::VOICE_CHANNEL_EDIT_MODAL_USER_LIMIT)
            ->setValue($vc->user_limit)
            ->setPlaceholder('Кількість учасників');
        $userLimitRow = ActionRow::new()->addComponent($userLimitInput);

        $interaction->showModal(
            'Редагування голосового каналу',
            self::VOICE_CHANNEL_EDIT_MODAL,
            [$nameRow, $userLimitRow],
            self::onModalSubmit($vc)
        );
    }

    private static function onModalSubmit(VoiceChannel $vc): callable
    {
        return function (Interaction $interaction, Collection $components) use ($vc) {
            $name = $components[self::VOICE_CHANNEL_EDIT_MODAL_NAME]->value;
            $userLimit = (int)$components[self::VOICE_CHANNEL_EDIT_MODAL_USER_LIMIT]->value;
            /** @var Channel $updatedVc */
            $updatedVc = $interaction->guild->channels->create([
                'id' => $vc->vc_discord_id,
                'name' => $name,
                'type' => Channel::TYPE_VOICE,
                'user_limit' => max($userLimit, 1),
                'parent_id' => $vc->category
            ], true);
            $interaction->guild->channels->save($updatedVc)->done(function () use ($interaction, $vc, $updatedVc) {
                $vc->name = $updatedVc->name;
                $vc->user_limit = $updatedVc->user_limit;
                $vc->category = $updatedVc->parent_id;
                $vc->save();
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Редагування пройшло успішно!'), true);
                $interaction->acknowledge();
            });
        };
    }
}
