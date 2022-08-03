<?php

namespace App\Discord\SlashCommands;

use App\Lfg;
use Closure;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use JetBrains\PhpStorm\ArrayShape;

class LfgSlashCommandListener implements SlashCommandListenerInterface
{
    private array $buttons = [];

    public function __construct(private readonly Discord $discord)
    {
    }

    public function listen(): Closure
    {
        return function (Interaction $interaction) {
            $activityInput = TextInput::new('Ім\'я активності', TextInput::STYLE_SHORT, 'activity_name')
                ->setPlaceholder('Ім\'я активності');
            $activityRow = ActionRow::new()->addComponent($activityInput);

            $descriptionInput = TextInput::new('Опис активності', TextInput::STYLE_PARAGRAPH, 'description')
                ->setPlaceholder('Опис активності');
            $descriptionRow = ActionRow::new()->addComponent($descriptionInput);

            $dateInput = TextInput::new('Дата початку', TextInput::STYLE_SHORT, 'date')
                ->setPlaceholder('Г:ХВ число місяць (напр.: 9:30 3 12, 20:00 15 7)');
            $dateRow = ActionRow::new()->addComponent($dateInput);

            $groupSizeInput = TextInput::new('Величина групи', TextInput::STYLE_SHORT, 'group_size')
                ->setPlaceholder('Число учасників у групі');
            $groupSizeRow = ActionRow::new()->addComponent($groupSizeInput);

            $interaction->showModal(
                'Створення Групи',
                'custom_id',
                [$activityRow, $descriptionRow, $dateRow, $groupSizeRow],
                function (Interaction $interaction, Collection $components) {
                    $buttonActionRow = ActionRow::new();

                    foreach ($this->activityTypes() as $type => $item) {
                        $btn = Button::new(Button::STYLE_SECONDARY, $type)->setLabel($item['label']);
                        // Saving buttons here to have the ability to clean up listeners.
                        $this->buttons[] = $btn;
                        $btn->setListener($this->addButtonListener($components, $this->discord), $this->discord, true);
                        $buttonActionRow->addComponent($btn);
                    }

                    $buttonRow = MessageBuilder::new()->addComponent($buttonActionRow)->setContent('Оберіть тип активності:');
                    return $interaction->respondWithMessage($buttonRow, true);
                }
            );
        };
    }

    private function addButtonListener(Collection $components, Discord $discord): Closure
    {
        return function (Interaction $interaction) use ($components, $discord) {
            $type = $interaction->data->custom_id;
            $date = \DateTime::createFromFormat('G:i j n O', trim($components['date']->getRawAttributes()['value']) . ' +0300');
            if ($date === false) {
                return $interaction->updateMessage(MessageBuilder::new()->setContent('Неправильний формат дати. Формат: Г:ХВ (години:хвилини у 24 годинному форматі) число місяць (наприклад: 9:30 3 12, 20:00 15 7, 13:30 22 9).'), true);
            }

            $owner = $interaction->member->user->id;
            $title = $components['activity_name']->value;
            $description = $components['description']->value;
            $groupSize = (int)$components['group_size']->value;

            $lfg = Lfg::create([
                'owner' => $owner,
                'title' => $title,
                'description' => $description,
                'group_size' => $groupSize === 0 ? 6 : $groupSize,
                'type' => $type,
                'time_of_start' => $date
            ]);

            $embed = new Embed($discord);
            $embed->setThumbnail($this->activityTypes()[$type]['thumbnail']);
            $embed->setColor($this->activityTypes()[$type]['color']);
            $embed->setTitle($title);
            $embed->addFieldValues('Опис', $description);
            $embed->addFieldValues('Дата', '<t:' . $date->getTimestamp() . ':f>');
            $embed->setFooter('ID: ' . $lfg->uuid . ' | Ініціатор: ' . ($interaction->member->nick ?? $interaction->member->username));

            $iWantToGoBtn = Button::new(Button::STYLE_SUCCESS)->setLabel('Хочу піти');
            $reserveBtn = Button::new(Button::STYLE_PRIMARY)->setLabel('Резерв');
            $removeRegistrationBtn = Button::new(Button::STYLE_SECONDARY)->setLabel('Прибрати реєстрацію');
            $removeGroupBtn = Button::new(Button::STYLE_DANGER)->setLabel('Видалити групу');

            $embedActionRow = ActionRow::new();
            $embedActionRow
                ->addComponent($iWantToGoBtn)
                ->addComponent($reserveBtn)
                ->addComponent($removeRegistrationBtn)
                ->addComponent($removeGroupBtn);

            $embeddedMessage = MessageBuilder::new()->addEmbed($embed)->addComponent($embedActionRow);

            $iWantToGoBtn->setListener($this->iWantToGoBtnListener($embedActionRow), $discord);
            $reserveBtn->setListener($this->reserveBtnListener($embedActionRow), $discord);
            $removeRegistrationBtn->setListener($this->removeRegistrationBtnListener($embedActionRow), $discord);
            $removeGroupBtn->setListener($this->removeGroupBtnListener(), $discord);

            $interaction->updateMessage(MessageBuilder::new()->setContent('Створення пройшло успішно! Тримай кавунця - :watermelon:'));
            $interaction->channel->sendMessage($embeddedMessage)->done(function (Message $message) use ($lfg) {
                $lfg->discord_id = $message->id;
                $lfg->save();
            });

            // Clean up listeners.
            foreach ($this->buttons as $btn) {
                $btn->setListener(null, $discord);
            }
        };
    }

    private function iWantToGoBtnListener(ActionRow $embedActionRow): Closure
    {
        return function (Interaction $interaction) use ($embedActionRow) {
            /** @var Embed $theEmbed */
            $theEmbed = $interaction->message->embeds->first();
            $userId = $interaction->member->user->id;
            $fields = $theEmbed->fields;

            $lfg = $this->getLfgFromEmbed($interaction);
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
                                $fields->offsetSet('Резерв', ['value' => $this->assembleAtUsersString($res), 'name' => 'Резерв', 'inline' => false]);
                                $lfg->reserve = implode('|', $res);
                            }
                        }
                        $parts[] = $userId;
                        $fields->offsetSet('Учасники', ['value' => $this->assembleAtUsersString($parts), 'name' => 'Учасники', 'inline' => false]);
                        $lfg->participants = implode('|', $parts);
                        $lfg->save();
                    } else {
                        if (!empty($reserve)) {
                            $res = explode('|', $reserve);
                            if (!in_array($userId, $res, true)) {
                                $res[] = $userId;
                                $fields->offsetSet('Резерв', ['value' => $this->assembleAtUsersString($res), 'name' => 'Резерв', 'inline' => false]);
                                $lfg->reserve = implode('|', $res);
                                $lfg->save();
                            }
                        } else {
                            $lfg->reserve = $userId;
                            $lfg->save();
                            $fields->offsetSet('Резерв', ['value' => "<@$userId>", 'name' => 'Резерв', 'inline' => false]);
                        }
                    }
                }
            } else {
                $lfg->participants = $userId;
                $reserveField = $fields->pull('Резерв');
                $fields->offsetSet('Учасники', ['value' => "<@$userId>", 'name' => 'Учасники', 'inline' => false]);
                if (!empty($reserve)) {
                    $res = explode('|', $reserve);
                    if (in_array($userId, $res, true)) {
                        unset($res[array_search($userId, $res)]);
                        if (empty($res)) {
                            $lfg->reserve = null;
                        } else {
                            $fields->offsetSet('Резерв', ['value' => $this->assembleAtUsersString($res), 'name' => 'Резерв', 'inline' => false]);
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

            $interaction->message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
        };
    }

    private function reserveBtnListener(ActionRow $embedActionRow): Closure
    {
        return function (Interaction $interaction) use ($embedActionRow) {
            /** @var Embed $theEmbed */
            $theEmbed = $interaction->message->embeds->first();
            $userId = $interaction->member->user->id;
            $fields = $theEmbed->fields;

            $lfg = $this->getLfgFromEmbed($interaction);
            $participants = $lfg->participants;
            $reserve = $lfg->reserve;

            if (!empty($reserve)) {
                $res = explode('|', $reserve);
                if (!in_array($userId, $res, true)) {
                    $res[] = $userId;
                    $fields->offsetSet('Резерв', ['value' => $this->assembleAtUsersString($res), 'name' => 'Резерв', 'inline' => false]);
                    $lfg->reserve = implode('|', $res);
                }
            } else {
                $lfg->reserve = $userId;
                $fields->offsetSet('Резерв', ['value' => "<@$userId>", 'name' => 'Резерв', 'inline' => false]);
            }

            $parts = explode('|', $participants);
            if (!empty($participants) && in_array($userId, $parts, true)) {
                unset($parts[array_search($userId, $parts)]);
                if (empty($parts)) {
                    $fields->pull('Учасники');
                    $lfg->participants = null;
                } else {
                    $fields->offsetSet('Учасники', ['value' => $this->assembleAtUsersString($parts), 'name' => 'Учасники', 'inline' => false]);
                    $lfg->participants = implode('|', $parts);
                }
            }
            $lfg->save();

            $theEmbed->offsetUnset('fields');
            foreach ($fields->toArray() as $field) {
                $theEmbed->addField($field);
            }

            $interaction->message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
        };
    }

    private function removeRegistrationBtnListener(ActionRow $embedActionRow): Closure
    {
        return function (Interaction $interaction) use ($embedActionRow) {
            /** @var Embed $theEmbed */
            $theEmbed = $interaction->message->embeds->first();
            $userId = $interaction->member->user->id;
            $fields = $theEmbed->fields;
            $lfg = $this->getLfgFromEmbed($interaction);
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
                    $fields->offsetSet('Учасники', ['value' => $this->assembleAtUsersString($parts), 'name' => 'Учасники', 'inline' => false]);
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
                    $fields->offsetSet('Резерв', ['value' => $this->assembleAtUsersString($res), 'name' => 'Резерв', 'inline' => false]);
                    $lfg->reserve = implode('|', $res);
                }
            }

            $lfg->save();

            $theEmbed->offsetUnset('fields');
            foreach ($fields->toArray() as $field) {
                $theEmbed->addField($field);
            }

            $interaction->message->edit(MessageBuilder::new()->addEmbed($theEmbed)->addComponent($embedActionRow));
        };
    }

    private function removeGroupBtnListener(): Closure
    {
        return function (Interaction $interaction) {
            $userId = $interaction->member->user->id;
            $lfg = $this->getLfgFromEmbed($interaction);
            if ($lfg->owner === $userId) {
                $interaction->message->delete();
                $lfg->delete();
            }
        };
    }

    private function getLfgFromEmbed(Interaction $interaction): Lfg
    {
        $match = [];
        preg_match('/ID:\s(.*?)\s|/', $interaction->message->embeds[0]->footer->text, $match);
        return Lfg::find($match[1]);
    }

    private function assembleAtUsersString(array $userIds): string
    {
        return implode(', ', array_map(function ($id) {
            return "<@$id>";
        }, $userIds));
    }


    #[ArrayShape(['raid' => "string[]", 'pvp' => "string[]", 'pve' => "string[]", 'gambit' => "string[]", 'other' => "string[]"])]
    private function activityTypes(): array
    {
        return [
            'raid' => [
                'label' => 'Рейд',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/8b1bfd1c1ce1cab51d23c78235a6e067.png',
                'color' => '#f0c907'
            ],
            'pvp' => [
                'label' => 'PVP',
                'thumbnail' => 'https://www.bungie.net//common/destiny2_content/icons/cc8e6eea2300a1e27832d52e9453a227.png',
                'color' => '#f00707'
            ],
            'pve' => [
                'label' => 'PVE',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/f2154b781b36b19760efcb23695c66fe.png',
                'color' => '#024ad9'
            ],
            'gambit' => [
                'label' => 'Ґамбіт',
                'thumbnail' => 'https://www.bungie.net/common/destiny2_content/icons/fc31e8ede7cc15908d6e2dfac25d78ff.png',
                'color' => '#00b80c'
            ],
            'other' => [
                'label' => 'Інше',
                'thumbnail' => 'https://png2.cleanpng.com/sh/0cb9e768019d24d86f4e5055d88f6bf2/L0KzQYq3VsEyN517R91yc4Pzfri0hPV0fJpzkZ87LXTog8XwjwkufJlqReZqa3XxPbzwjvcucJJxh597ZXHmeH79ifRmNaV3eehubHX1PbXskCRqdqoyjORqboPzccPsjwQuaZ51ReJ3Zz3mfLr3ggJ1NZd3fZ8AY3bpRrS9g8NkQWNqT5CBNEK5QIiCVsE2PmE3TKU8MEi1RIm4TwBvbz==/kisspng-destiny-2-destiny-the-taken-king-halo-reach-vide-traveler-destiny-transparent-amp-png-clipart-fre-5cff6c6c3c92e7.6426079615602433082481.png',
                'color' => '#878787'
            ]
        ];
    }
}
