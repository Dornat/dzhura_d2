<?php

namespace App\Discord\SlashCommands;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Lfg\ActivityTypes;
use App\Lfg;
use Closure;
use DateTime;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\InteractionType;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Activity;
use JetBrains\PhpStorm\ArrayShape;

class LfgSlashCommandListener implements SlashCommandListenerInterface
{
    public const I_WANT_TO_GO_BTN = 'i_want_to_go_btn';
    public const RESERVE_BTN = 'reserve_btn';
    public const REMOVE_REGISTRATION_BTN = 'remove_registration_btn';
    public const REMOVE_GROUP_BTN = 'remove_group_btn';
    public const LFG = 'lfg';
    public const LFG_MODAL = 'lfg_modal';

    private static array $buttons = [];

    public static function act(Interaction $interaction): void
    {
        if ($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::LFG) {
            self::showModal($interaction);
        } else if ($interaction->data->custom_id === self::I_WANT_TO_GO_BTN) {
            self::iWantToGoBtn($interaction);
            $interaction->acknowledge();
        } else if ($interaction->data->custom_id === self::RESERVE_BTN) {
            self::reserveBtn($interaction);
            $interaction->acknowledge();
        } else if ($interaction->data->custom_id === self::REMOVE_REGISTRATION_BTN) {
            self::removeRegistrationBtn($interaction);
            $interaction->acknowledge();
        } else if ($interaction->data->custom_id === self::REMOVE_GROUP_BTN) {
            self::removeGroupBtn($interaction);
            $interaction->acknowledge();
        }
    }

    private static function showModal(Interaction $interaction): void
    {
        $activityInput = TextInput::new('Ім\'я активності', TextInput::STYLE_SHORT, 'activity_name')
            ->setPlaceholder('Ім\'я активності');
        $activityRow = ActionRow::new()->addComponent($activityInput);

        $descriptionInput = TextInput::new('Опис активності', TextInput::STYLE_PARAGRAPH, 'description')
            ->setPlaceholder('Опис активності');
        $descriptionRow = ActionRow::new()->addComponent($descriptionInput);

        $dateInput = TextInput::new('Дата початку (Г:ХВ число місяць)', TextInput::STYLE_SHORT, 'date')
            ->setPlaceholder('Приклади: 9:30 4 12, 20:00 15 7, 23:40 28 9');
        $dateRow = ActionRow::new()->addComponent($dateInput);

        $groupSizeInput = TextInput::new('Величина групи', TextInput::STYLE_SHORT, 'group_size')
            ->setPlaceholder('Число учасників у групі');
        $groupSizeRow = ActionRow::new()->addComponent($groupSizeInput);

        $interaction->showModal(
            'Створення Групи',
            'lfg_modal',
            [$activityRow, $descriptionRow, $dateRow, $groupSizeRow]
        );
    }

    public static function onModalSubmit(Interaction $interaction, Discord $discord): void
    {
        $buttonActionRow = ActionRow::new();

        $collection = new Collection();
        foreach ($interaction->data->components as $component) {
            $collection->set($component->components->first()->custom_id, $component->components->first()->value);
        }

        self::$buttons = [];

        foreach (ActivityTypes::list() as $type => $item) {
            $btn = Button::new(Button::STYLE_SECONDARY, $type)->setLabel($item['label']);
            // Saving buttons here to have the ability to clean up listeners.
            self::$buttons[] = $btn;
            $btn->setListener(self::onActivityTypeSubmit($collection, $discord), $discord, true);
            $buttonActionRow->addComponent($btn);
        }

        $buttonRow = MessageBuilder::new()->addComponent($buttonActionRow)->setContent('Оберіть тип активності:');
        $interaction->respondWithMessage($buttonRow, true);
    }

    private static function onActivityTypeSubmit(Collection $components, Discord $discord): Closure
    {
        return function (Interaction $interaction) use ($components, $discord) {
            if ($interaction->data->custom_id === ActivityTypes::RAID) {
                $raidSelect = SelectMenu::new(ActivityTypes::RAID_SELECT);
                foreach (ActivityTypes::list()[ActivityTypes::RAID]['types'] as $raidType => $raidTypeObj) {
                    $raidSelect->addOption(Option::new($raidTypeObj['label'], $raidType));
                }
                self::$buttons[] = $raidSelect;
                $raidSelect->setListener(self::onActivityTypeSubmit($components, $discord), $discord);
                $interaction->updateMessage(MessageBuilder::new()
                    ->setContent('Обери назву рейду:')
                    ->addComponent($raidSelect)
                );
                return;
            }

            $type = $interaction->data->custom_id === ActivityTypes::RAID_SELECT ? ActivityTypes::RAID : $interaction->data->custom_id;
            $raidType = $interaction->data->custom_id === ActivityTypes::RAID_SELECT ? $interaction->data->values[0] : null;
            $date = DateTime::createFromFormat('G:i j n O', trim($components['date']) . ' +0300');
            if ($date === false) {
                $interaction->updateMessage(MessageBuilder::new()->setContent('Неправильний формат дати. Формат: Г:ХВ (години:хвилини у 24 годинному форматі) число місяць (наприклад: 9:30 4 12, 20:00 15 7, 13:30 22 9).'));
                return;
            }

            $owner = $interaction->member->user->id;
            $title = $raidType ? ActivityTypes::list()[ActivityTypes::RAID]['types'][$raidType]['label'] : $components['activity_name'];
            $description = $components['description'];
            $groupSize = (int)$components['group_size'];

            $lfg = Lfg::create([
                'owner' => $owner,
                'title' => $title,
                'description' => $description,
                'group_size' => $groupSize === 0 ? 6 : $groupSize,
                'type' => $type,
                'time_of_start' => $date
            ]);

            $embed = new Embed($discord);
            $embed->setThumbnail(ActivityTypes::list()[$type]['thumbnail']);
            $embed->setImage(self::getImageByType($raidType ?: $type));
            $embed->setColor(ActivityTypes::list()[$type]['color']);
            $embed->setTitle($title);
            $embed->addFieldValues('Опис', $description);
            $embed->addFieldValues('Дата', '<t:' . $date->getTimestamp() . ':f>');
            $embed->setFooter('ID: ' . $lfg->uuid . ' | Ініціатор: ' . ($interaction->member->nick ?? $interaction->member->username));

            $iWantToGoBtn = Button::new(Button::STYLE_SUCCESS)->setLabel('Хочу піти')->setCustomId(self::I_WANT_TO_GO_BTN);
            $reserveBtn = Button::new(Button::STYLE_PRIMARY)->setLabel('Резерв')->setCustomId(self::RESERVE_BTN);
            $removeRegistrationBtn = Button::new(Button::STYLE_SECONDARY)->setLabel('Прибрати реєстрацію')->setCustomId(self::REMOVE_REGISTRATION_BTN);
            $removeGroupBtn = Button::new(Button::STYLE_DANGER)->setLabel('Видалити групу')->setCustomId(self::REMOVE_GROUP_BTN);

            $embedActionRow = ActionRow::new();
            $embedActionRow
                ->addComponent($iWantToGoBtn)
                ->addComponent($reserveBtn)
                ->addComponent($removeRegistrationBtn)
                ->addComponent($removeGroupBtn);

            $embeddedMessage = MessageBuilder::new()->addEmbed($embed)->addComponent($embedActionRow);

            $interaction->updateMessage(MessageBuilder::new()->setContent('Створення пройшло успішно! Тримай кавунця - :watermelon:'));
            $interaction->channel->sendMessage($embeddedMessage)->done(function (Message $message) use ($lfg) {
                $lfg->discord_id = $message->id;
                $lfg->save();
            });

            // Clean up listeners.
            foreach (self::$buttons as $btn) {
                $btn->setListener(null, $discord);
            }
        };
    }

    private static function getImageByType(string $type): string
    {
        return match ($type) {
            ActivityTypes::PVE => ActivityTypes::list()[ActivityTypes::PVE]['image'],
            ActivityTypes::PVP => ActivityTypes::list()[ActivityTypes::PVP]['image'],
            ActivityTypes::GAMBIT => ActivityTypes::list()[ActivityTypes::GAMBIT]['image'],
            ActivityTypes::RAID_DSC => ActivityTypes::list()[ActivityTypes::RAID]['types'][ActivityTypes::RAID_DSC]['image'],
            ActivityTypes::RAID_GOS => ActivityTypes::list()[ActivityTypes::RAID]['types'][ActivityTypes::RAID_GOS]['image'],
            ActivityTypes::RAID_LW => ActivityTypes::list()[ActivityTypes::RAID]['types'][ActivityTypes::RAID_LW]['image'],
            ActivityTypes::RAID_VOD => ActivityTypes::list()[ActivityTypes::RAID]['types'][ActivityTypes::RAID_VOD]['image'],
            ActivityTypes::RAID_VOG => ActivityTypes::list()[ActivityTypes::RAID]['types'][ActivityTypes::RAID_VOG]['image'],
            default => '',
        };
    }

    private static function iWantToGoBtn(Interaction $interaction): void
    {
        /** @var Embed $theEmbed */
        $theEmbed = $interaction->message->embeds->first();
        $userId = $interaction->member->user->id;
        $fields = $theEmbed->fields;

        $lfg = LfgSlashCommandListener::getLfgFromEmbed($interaction);
        $participants = $lfg->participants;
        $reserve = $lfg->reserve;

        if (!empty($participants)) {
            $parts = explode('|', $participants);
            if (!in_array($userId, $parts, true)) {
                if (count($parts) < $lfg->group_size) {
                    $res = explode('|', $reserve);
                    if (!empty($reserve) && in_array($userId, $res, true)) {
                        unset($res[array_search($userId, $res)]);
                        if (empty($res)) {
                            $fields->pull('Резерв');
                            $lfg->reserve = null;
                        } else {
                            $fields->offsetSet('Резерв', [
                                'value' => SlashCommandHelper::assembleAtUsersString($res),
                                'name' => 'Резерв',
                                'inline' => false
                            ]);
                            $lfg->reserve = implode('|', $res);
                        }
                    }
                    $parts[] = $userId;
                    $fields->offsetSet('Учасники', [
                        'value' => SlashCommandHelper::assembleAtUsersString($parts),
                        'name' => 'Учасники',
                        'inline' => false
                    ]);
                    $lfg->participants = implode('|', $parts);
                    $lfg->save();
                } else {
                    if (!empty($reserve)) {
                        $res = explode('|', $reserve);
                        if (!in_array($userId, $res, true)) {
                            $res[] = $userId;
                            $fields->offsetSet('Резерв', [
                                'value' => SlashCommandHelper::assembleAtUsersString($res),
                                'name' => 'Резерв',
                                'inline' => false
                            ]);
                            $lfg->reserve = implode('|', $res);
                            $lfg->save();
                        }
                    } else {
                        $lfg->reserve = $userId;
                        $lfg->save();
                        $fields->offsetSet('Резерв', [
                            'value' => "<@$userId>",
                            'name' => 'Резерв',
                            'inline' => false
                        ]);
                    }
                }
            }
        } else {
            $lfg->participants = $userId;
            $reserveField = $fields->pull('Резерв');
            $fields->offsetSet('Учасники', [
                'value' => "<@$userId>",
                'name' => 'Учасники',
                'inline' => false
            ]);
            if (!empty($reserve)) {
                $res = explode('|', $reserve);
                if (in_array($userId, $res, true)) {
                    unset($res[array_search($userId, $res)]);
                    if (empty($res)) {
                        $lfg->reserve = null;
                    } else {
                        $fields->offsetSet('Резерв', [
                            'value' => SlashCommandHelper::assembleAtUsersString($res),
                            'name' => 'Резерв',
                            'inline' => false
                        ]);
                        $lfg->reserve = implode('|', $res);
                    }
                } else {
                    $fields->offsetSet('Резерв', $reserveField);
                }
            }
            $lfg->save();
        }

        $theEmbed->offsetUnset('fields');
        foreach ($fields->toArray() as $field) {
            $theEmbed->addField($field);
        }

        $embedActionRow = SlashCommandHelper::constructEmbedActionRowFromComponentRepository($interaction->message->components->first()->components);

        $interaction->message->edit(
            MessageBuilder::new()
                ->addEmbed($theEmbed)
                ->addComponent($embedActionRow)
        );
    }

    private static function reserveBtn(Interaction $interaction): void
    {
        /** @var Embed $theEmbed */
        $theEmbed = $interaction->message->embeds->first();
        $userId = $interaction->member->user->id;
        $fields = $theEmbed->fields;

        $lfg = self::getLfgFromEmbed($interaction);
        $participants = $lfg->participants;
        $reserve = $lfg->reserve;

        if (!empty($reserve)) {
            $res = explode('|', $reserve);
            if (!in_array($userId, $res, true)) {
                $res[] = $userId;
                $fields->offsetSet('Резерв', [
                    'value' => SlashCommandHelper::assembleAtUsersString($res),
                    'name' => 'Резерв',
                    'inline' => false
                ]);
                $lfg->reserve = implode('|', $res);
            }
        } else {
            $lfg->reserve = $userId;
            $fields->offsetSet('Резерв', [
                'value' => "<@$userId>",
                'name' => 'Резерв',
                'inline' => false
            ]);
        }

        $parts = explode('|', $participants);
        if (!empty($participants) && in_array($userId, $parts, true)) {
            unset($parts[array_search($userId, $parts)]);
            if (empty($parts)) {
                $fields->pull('Учасники');
                $lfg->participants = null;
            } else {
                $fields->offsetSet('Учасники', [
                    'value' => SlashCommandHelper::assembleAtUsersString($parts),
                    'name' => 'Учасники',
                    'inline' => false
                ]);
                $lfg->participants = implode('|', $parts);
            }
        }
        $lfg->save();

        $theEmbed->offsetUnset('fields');
        foreach ($fields->toArray() as $field) {
            $theEmbed->addField($field);
        }

        $embedActionRow = SlashCommandHelper::constructEmbedActionRowFromComponentRepository($interaction->message->components->first()->components);

        $interaction->message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
    }

    private static function removeRegistrationBtn(Interaction $interaction): void
    {
        /** @var Embed $theEmbed */
        $theEmbed = $interaction->message->embeds->first();
        $userId = $interaction->member->user->id;
        $fields = $theEmbed->fields;
        $lfg = self::getLfgFromEmbed($interaction);
        $participants = $lfg->participants;
        $reserve = $lfg->reserve;

        if (!empty($participants)) {
            $parts = explode('|', $participants);
            if (in_array($userId, $parts, true)) {
                unset($parts[array_search($userId, $parts)]);
            }

            if (empty($parts)) {
                $fields->pull('Учасники');
                $lfg->participants = null;
            } else {
                $fields->offsetSet('Учасники', [
                    'value' => SlashCommandHelper::assembleAtUsersString($parts),
                    'name' => 'Учасники',
                    'inline' => false
                ]);
                $lfg->participants = implode('|', $parts);
            }
        }

        if (!empty($reserve)) {
            $res = explode('|', $reserve);
            if (in_array($userId, $res, true)) {
                unset($res[array_search($userId, $res)]);
            }

            if (empty($res)) {
                $fields->pull('Резерв');
                $lfg->reserve = null;
            } else {
                $fields->offsetSet('Резерв', [
                    'value' => SlashCommandHelper::assembleAtUsersString($res),
                    'name' => 'Резерв',
                    'inline' => false
                ]);
                $lfg->reserve = implode('|', $res);
            }
        }

        $lfg->save();

        $theEmbed->offsetUnset('fields');
        foreach ($fields->toArray() as $field) {
            $theEmbed->addField($field);
        }

        $embedActionRow = SlashCommandHelper::constructEmbedActionRowFromComponentRepository($interaction->message->components->first()->components);

        $interaction->message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
    }

    private static function removeGroupBtn(Interaction $interaction): void
    {
        $userId = $interaction->member->user->id;
        $lfg = self::getLfgFromEmbed($interaction);
        if ($lfg->owner === $userId) {
            $interaction->message->delete();
            $lfg->delete();
        }
    }

    public static function getLfgFromEmbed(Interaction $interaction): Lfg
    {
        $match = [];
        preg_match('/ID:\s(.*?)\s|/', $interaction->message->embeds[0]->footer->text, $match);
        return Lfg::find($match[1]);
    }
}
