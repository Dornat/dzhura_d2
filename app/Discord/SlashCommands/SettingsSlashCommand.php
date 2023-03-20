<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\Settings\Factories\GlobalSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\VoiceCreateSettingsFactory;
use App\Discord\SlashCommands\Settings\SettingsDefaultObject;
use App\Discord\SlashCommands\Settings\SettingsObject;
use App\Setting;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;

class SettingsSlashCommand implements SlashCommandListenerInterface
{
    public const SETTINGS = 'settings';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::SETTINGS) {
            if (!$interaction->member->permissions->administrator) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора.'), true);
                return;
            }
            self::actResponse($interaction, $discord);
        } else if ($interaction->data->custom_id === GlobalSettingsFactory::SETTINGS_GLOBAL_TIMEZONE_SELECT) {
            GlobalSettingsFactory::actOnGlobalTimezoneSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === GlobalSettingsFactory::SETTINGS_GLOBAL_OPEN_MODAL_BTN) {
            GlobalSettingsFactory::actOnGlobalSettingsModalOpenBtn($interaction, $discord);
        } else if ($interaction->type === InteractionType::MESSAGE_COMPONENT && $interaction->data->custom_id === VoiceCreateSettingsFactory::SETTINGS_VC_CREATION_ROLES_SELECT) {
            VoiceCreateSettingsFactory::actOnVCRoleSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === VoiceCreateSettingsFactory::SETTINGS_VC_OPEN_MODAL_BTN) {
            VoiceCreateSettingsFactory::actOnVoiceCreateSettingsModalOpenBtn($interaction, $discord);
        } else if ($interaction->data->custom_id === VoiceCreateSettingsFactory::SETTINGS_VC_MODAL) {
            VoiceCreateSettingsFactory::actOnVoiceCreateSettingsModalSubmit($interaction, $discord);
        }
    }

    public static function actResponse(Interaction $interaction, Discord $discord): void
    {
        $guildId = $interaction->guild_id;

        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (is_null($settingRow)) {
            $settingsObject = SettingsDefaultObject::get();
        } else {
            $settingsObject = SettingsObject::getFromGuildId($guildId);
        }

        $globalSettingsFactory = new GlobalSettingsFactory($discord, $settingsObject);
        $voiceCreateFactory = new VoiceCreateSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> Тут можна налаштувати деякі функції і команди бота.\n> Для того щоби побачити зміни, потрібно перезапустити команду `/settings`.\n> Через обмеженість discord API зміна налаштувань через поля селекторів (напр. ролі для `/voicecreate`) розташовані окремими полями, а введення налаштувань для інших полів винесені в модальні вікна, які можна викликати натиснувши на відповідні кнопки.")
            ->addEmbed(
                $globalSettingsFactory->embed,
                $voiceCreateFactory->embed
            )
            ->setComponents([
                $globalSettingsFactory->timezoneSelect,
                $globalSettingsFactory->modalBtnActionRow,
                $voiceCreateFactory->authorizedRolesSelect,
                $voiceCreateFactory->modalBtnActionRow
            ]);

        $interaction->respondWithMessage($msg, true);
    }
}