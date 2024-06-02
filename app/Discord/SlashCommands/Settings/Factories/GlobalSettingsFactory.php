<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Setting;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Interactions\Interaction;

class GlobalSettingsFactory
{
    public const SETTINGS_GLOBAL_TIMEZONE_SELECT = 'settings_global_timezone_select';
    public const SETTINGS_GLOBAL_OPEN_MODAL_BTN = 'settings_global_open_modal_btn';
    public const SETTINGS_GLOBAL_MODAL = 'settings_global_modal';

    public Embed $embed;
    public SelectMenu $timezoneSelect;
    public ActionRow $modalBtnActionRow;

    public function __construct(Discord $discord, SettingsObject $settingsObject)
    {
        $this->embed = new Embed($discord);
        $this->embed->setColor('#ebf5ee');
        $this->embed->setTitle('Глобальні налаштування бота');
        $this->embed->addFieldValues('Часова зона', $settingsObject->global->timeZone);

        $this->timezoneSelect = SelectMenu::new(self::SETTINGS_GLOBAL_TIMEZONE_SELECT)->setPlaceholder('Часовий пояс, в якому буде жити бот');
        $this->timezoneSelect->addOption(new Option('UTC', 'UTC'));
        $this->timezoneSelect->addOption(new Option('Київський час', 'Europe/Kiev'));
        $this->timezoneSelect->addOption(new Option('Варшава', 'Europe/Warsaw'));
        $this->timezoneSelect->addOption(new Option('Берлін', 'Europe/Berlin'));

        $globalSettingsModalBtn = Button::new(Button::STYLE_SECONDARY)
            ->setLabel('Зміна глобальних налаштувань')
            ->setCustomId(self::SETTINGS_GLOBAL_OPEN_MODAL_BTN);
        $this->modalBtnActionRow = ActionRow::new()->addComponent($globalSettingsModalBtn);
    }

    public static function actOnGlobalTimezoneSelect(Interaction $interaction, Discord $discord): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $settingsObject->global->timeZone = $interaction->data->values[0];

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $newEmbed = $interaction->message->embeds->first();
        /** @var Field $field */
        $field = $newEmbed->fields->first();
        $newEmbed->offsetUnset('fields');
        $field->offsetSet('value', $settingsObject->global->timeZone);
        $newEmbed->addField($field);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->setContent($interaction->message->content)
                ->addEmbed($newEmbed)
                ->setComponents(SlashCommandHelper::constructComponentsForMessageBuilderFromInteraction($interaction))
        );
    }

    public static function actOnGlobalSettingsModalOpenBtn(Interaction $interaction, Discord $discord): void
    {
        $activityInput = TextInput::new('Test', TextInput::STYLE_SHORT, 'test')
            ->setPlaceholder('Test');
        $activityRow = ActionRow::new()->addComponent($activityInput);
        $interaction->showModal(
            'Test',
            self::SETTINGS_GLOBAL_MODAL,
            [$activityRow]
        );
    }
}
