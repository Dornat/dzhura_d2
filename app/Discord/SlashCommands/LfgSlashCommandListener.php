<?php

namespace App\Discord\SlashCommands;

use Closure;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;

class LfgSlashCommandListener implements SlashCommandListenerInterface
{

    /**
     * @var array
     */
    private $buttons = [];

    public function __construct(private Discord $discord)
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
}
