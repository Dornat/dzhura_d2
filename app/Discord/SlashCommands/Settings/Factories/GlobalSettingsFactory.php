<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\SlashCommands\Settings\SettingsDefaultObject;
use App\Discord\SlashCommands\Settings\SettingsObject;
use App\Setting;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\Components\TextInput;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
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
        $this->timezoneSelect->addOption(new Option('Київський час', 'Europe/Kyiv'));
        $this->timezoneSelect->addOption(new Option('Варшава', 'Europe/Warsaw'));
        $this->timezoneSelect->addOption(new Option('Берлін', 'Europe/Berlin'));

        $globalSettingsModalBtn = Button::new(Button::STYLE_SECONDARY)
            ->setLabel('Зміна глобальних налаштувань')
            ->setCustomId(self::SETTINGS_GLOBAL_OPEN_MODAL_BTN);
        $this->modalBtnActionRow = ActionRow::new()->addComponent($globalSettingsModalBtn);
    }

    public static function actOnGlobalTimezoneSelect(Interaction $interaction, Discord $discord): void
    {
        $settingsModel = Setting::where('guild_id', $interaction->guild_id)->first();

        if (!is_null($settingsModel)) {
            $settingsObject = new SettingsObject($settingsModel->object);
            $settingsObject->global->timeZone = $interaction->data->values[0];
        } else {
            $settingsObject = SettingsDefaultObject::get();
            $settingsModel = new Setting();
            $settingsModel->guild_id = $interaction->guild_id;
            $settingsModel->created_by = $interaction->member->user->id;
            $settingsObject->global->timeZone = $interaction->data->values[0];
        }

        /** @var Setting $settingsModel object */
        $settingsModel->object = json_encode($settingsObject);
        $settingsModel->updated_by = $interaction->member->user->id;
        $settingsModel->save();

        $interaction->acknowledge();
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