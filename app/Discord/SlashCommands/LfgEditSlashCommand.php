<?php

namespace App\Discord\SlashCommands;

use App\Discord\Helpers\SlashCommandHelper;
use App\Lfg;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\InteractionType;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class LfgEditSlashCommand implements SlashCommandListenerInterface
{
    public const LFG_EDIT = 'lfgedit';
    public const LFG_EDIT_MODAL = 'lfgedit_modal';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if (!($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::LFG_EDIT)) {
            return;
        }

        $groupId = $interaction->data->options['id']->value;
        $userId = $interaction->member->user->id;

        $lfg = Lfg::find($groupId);
        if (empty($lfg)) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Групи з таким ідентифікатором не існує.'), true);
            return;
        }

        if ($lfg->owner === $userId) {
            list($activityInput, $descriptionInput, $dateInput, $groupSizeInput) = LfgSlashCommandListener::buildModalInputs();

            $activityInput->setValue($lfg->title);
            $activityRow = ActionRow::new()->addComponent($activityInput);

            $descriptionInput->setValue($lfg->description);
            $descriptionRow = ActionRow::new()->addComponent($descriptionInput);

            $dateInput->setValue($lfg->time_of_start);
            $dateRow = ActionRow::new()->addComponent($dateInput);

            $groupSizeInput->setValue($lfg->group_size);
            $groupSizeRow = ActionRow::new()->addComponent($groupSizeInput);

            $interaction->showModal(
                'Редагування Групи',
                self::LFG_EDIT_MODAL,
                [$activityRow, $descriptionRow, $dateRow, $groupSizeRow],
                self::onModalSubmit($lfg)
            );
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent('Ти не можеш редагувати дані групи, тому що ти не є її ініціатором.'), true);
        }

        $interaction->acknowledge();
    }

    private static function onModalSubmit(Lfg $lfg): callable
    {
        return function (Interaction $interaction, Collection $components) use ($lfg) {
            $interaction->channel->messages->fetch($lfg->discord_id)->then(function (Message $message) use ($components, $lfg) {
                $updatedTitle = $components['activity_name']->value;
                $updatedDescription = $components['description']->value;
                $updatedDate = LfgSlashCommandListener::createFromLfgDate($components['date']->value);
                $updatedGroupSize = $components['group_size']->value;

                /** @var Embed $theEmbed */
                $theEmbed = $message->embeds->first();
                $fields = $theEmbed->fields;

                $lfg->title = $updatedTitle;
                $lfg->description = $updatedDescription;
                $lfg->time_of_start = $updatedDate;
                $lfg->group_size = ($updatedGroupSize < 1 || $updatedGroupSize > 42) ? 42 : $updatedGroupSize;
                $lfg->save();

                $theEmbed->setTitle($updatedTitle);
                $fields->offsetSet('Опис', [
                    'value' => $updatedDescription,
                    'name' => 'Опис',
                    'inline' => false
                ]);
                $fields->offsetSet('Дата', [
                    'value' => '<t:' . $updatedDate->getTimestamp() . ':f>',
                    'name' => 'Дата',
                    'inline' => false
                ]);

                $theEmbed->offsetUnset('fields');
                foreach ($fields->toArray() as $field) {
                    $theEmbed->addField($field);
                }

                $embedActionRow = SlashCommandHelper::constructEmbedActionRowFromComponentRepository($message->components->first()->components);

                $message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
            })->then(function () use ($interaction) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Редагування пройшло успішно!'), true);
                $interaction->acknowledge();
            });
        };
    }
}
