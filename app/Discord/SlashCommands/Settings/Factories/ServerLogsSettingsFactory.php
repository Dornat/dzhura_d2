<?php

namespace App\Discord\SlashCommands\Settings\Factories;

use App\Discord\Helpers\SlashCommandHelper;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Discord\SlashCommands\Settings\SelectMenuChannels;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class ServerLogsSettingsFactory
{
    public const SETTINGS_SERVER_LOGS_IS_ACTIVE_SELECT = 'settings_server_logs_is_active_select';
    public const SETTINGS_SERVER_LOGS_SEND_MESSAGES_CHANNEL_SELECT = 'settings_server_logs_send_messages_channel_select';

    public Embed $embed;
    public SelectMenu $activationSelect;
    public SelectMenuChannels $sendMessagesChannelSelect;

    public function __construct(Discord $discord, SettingsObject $settingsObject)
    {
        $this->embed = new Embed($discord);
        $this->embed->setColor('#34eb4c');
        $this->embed->setTitle('Налаштування логів на сервері');
        $this->embed->addField(...self::assembleEmbedFields($settingsObject));

        $this->activationSelect = SelectMenu::new(self::SETTINGS_SERVER_LOGS_IS_ACTIVE_SELECT)
            ->setPlaceholder('Активація логування на сервері')
            ->addOption(new Option('Деактивовано', 0))
            ->addOption(new Option('Активовано', 1));

        $this->sendMessagesChannelSelect = SelectMenuChannels::new(self::SETTINGS_SERVER_LOGS_SEND_MESSAGES_CHANNEL_SELECT)
            ->setChannelTypes([SelectMenuChannels::GUILD_TEXT_CHANNEL_TYPE])
            ->setPlaceholder('Вказати власний канал')
            ->setMinValues(0)
            ->setMaxValues(1);
    }

    private static function assembleEmbedFields(SettingsObject $settingsObject): array
    {
        return [
            [
                'name' => 'Чи активовані логи серверу',
                'value' => $settingsObject->serverLogs->active ? 'Активовано' : 'Деактивовано',
                'inline' => false,
            ],
            [
                'name' => 'Канал, куди будуть відправлятися логи',
                'value' => !empty($settingsObject->serverLogs->sendMessagesChannel) ? '<#' . $settingsObject->serverLogs->sendMessagesChannel . '>' : 'Не обрано',
                'inline' => false,
            ],
        ];
    }

    public static function actOnActivateSelect(Interaction $interaction): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        /** @var SettingsObject $settingsObject */
        $settingsObject->serverLogs->active = (bool)$interaction->data->values[0];
        SlashCommandHelper::updateSettingsModelAndEmbed($settingsObject, $settingsModel, $interaction, self::assembleEmbedFields($settingsObject));
    }

    public static function actOnSendMessagesChannelSelect(Interaction $interaction): void
    {
        list($settingsObject, $settingsModel) = SettingsObject::getFromInteractionOrGetDefault($interaction, true);
        $selectedChannel = $interaction->data->resolved?->channels->first();
        if (is_null($selectedChannel)) {
            $interaction->acknowledge();
            return;
        }

        /** @var SettingsObject $settingsObject */
        $settingsObject->serverLogs->sendMessagesChannel = $selectedChannel->id;
        SlashCommandHelper::updateSettingsModelAndEmbed($settingsObject, $settingsModel, $interaction, self::assembleEmbedFields($settingsObject));
    }
}
