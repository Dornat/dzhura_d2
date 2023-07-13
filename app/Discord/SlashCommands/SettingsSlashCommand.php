<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\Settings\Factories\GlobalSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\LevelsSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\VoiceCreateSettingsFactory;
use App\Discord\SlashCommands\Settings\Objects\SettingsDefaultObject;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
use App\Setting;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;

class SettingsSlashCommand implements SlashCommandListenerInterface
{
    public const SETTINGS = 'settings';
    public const GLOBAL = 'global';
    public const VC = 'voicecreate';
    public const LEVELS = 'levels';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::SETTINGS) {
            if (!$interaction->member->permissions->administrator) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
                return;
            }

            if ($interaction->data->options->first()->name === self::VC) {
                self::actVCResponse($interaction, $discord);
            } else if ($interaction->data->options->first()->name === self::LEVELS) {
                self::actLevelsResponse($interaction, $discord);
            } else {
                self::actGlobalResponse($interaction, $discord);
            }
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
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ACTIVATE_SELECT) {
            LevelsSettingsFactory::actOnActivateSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ANNOUNCEMENT_CHANNEL_SELECT) {
            LevelsSettingsFactory::actOnAnnouncementChannelSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::CUSTOM_CHANNEL_SELECT) {
            LevelsSettingsFactory::actOnCustomChannelSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_BTN) {
            LevelsSettingsFactory::actOnCustomizeAnnouncementMessageBtn($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ROLE_REWARDS_TYPE_SELECT) {
            LevelsSettingsFactory::actOnRoleRewardsTypeSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::REMOVE_ROLE_REWARDS_ON_DEMOTION_SELECT) {
            LevelsSettingsFactory::actOnRemoveRoleRewardsOnDemotionSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ROLE_REWARDS_LEVEL_NUMBER_SELECT) {
            LevelsSettingsFactory::actOnRoleRewardsLevelNumberSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ROLE_REWARDS_LEVEL_ROLE_SELECT) {
            LevelsSettingsFactory::actOnRoleRewardsLevelRoleSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::ROLE_REWARDS_BTN_CLEAR) {
            LevelsSettingsFactory::actOnRoleRewardsBtnClear($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::XP_RATE_SELECT) {
            LevelsSettingsFactory::actOnXPRateSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::XP_RATE_ROLE_SELECT) {
            LevelsSettingsFactory::actOnXPRateRoleSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::XP_RATE_ROLE_RATE_SELECT) {
            LevelsSettingsFactory::actOnXPRateRoleRateSelect($interaction, $discord);
        } else if ($interaction->data->custom_id === LevelsSettingsFactory::XP_RATE_ROLE_RATE_BTN_CLEAR) {
            LevelsSettingsFactory::actOnXPRateRoleRateBtnClear($interaction, $discord);
        }
    }

    public static function actGlobalResponse(Interaction $interaction, Discord $discord): void
    {
        $guildId = $interaction->guild_id;

        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (is_null($settingRow)) {
            $settingsObject = SettingsDefaultObject::get();
        } else {
            $settingsObject = SettingsObject::getFromGuildId($guildId);
        }

        $globalSettingsFactory = new GlobalSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> Для того щоби побачити зміни, потрібно перезапустити команду `/settings`.\n> Через обмеженість discord API зміна налаштувань через поля селекторів розташовані окремими полями, а введення налаштувань для інших полів винесені в модальні вікна, які можна викликати натиснувши на відповідні кнопки.")
            ->addEmbed(
                $globalSettingsFactory->embed,
            )
            ->setComponents([
                $globalSettingsFactory->timezoneSelect,
                $globalSettingsFactory->modalBtnActionRow,
            ]);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actVCResponse(Interaction $interaction, Discord $discord): void
    {
        $guildId = $interaction->guild_id;

        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (is_null($settingRow)) {
            $settingsObject = SettingsDefaultObject::get();
        } else {
            $settingsObject = SettingsObject::getFromGuildId($guildId);
        }

        $voiceCreateFactory = new VoiceCreateSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> Для того щоби побачити зміни, потрібно перезапустити команду `/settings`.\n> Через обмеженість discord API зміна налаштувань через поля селекторів розташовані окремими полями, а введення налаштувань для інших полів винесені в модальні вікна, які можна викликати натиснувши на відповідні кнопки.")
            ->addEmbed(
                $voiceCreateFactory->embed
            )
            ->setComponents([
                $voiceCreateFactory->authorizedRolesSelect,
                $voiceCreateFactory->modalBtnActionRow,
            ]);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actLevelsResponse(Interaction $interaction, Discord $discord): void
    {
        $guildId = $interaction->guild_id;

        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (is_null($settingRow)) {
            $settingsObject = SettingsDefaultObject::get();
        } else {
            $settingsObject = SettingsObject::getFromGuildId($guildId);
        }

        if ($interaction->data->options->first()->options->first()->name === LevelsSettingsFactory::ACTIVATE) {
            LevelsSettingsFactory::actOnActivateCommand($interaction, $discord, $settingsObject);
        } else if ($interaction->data->options->first()->options->first()->name === LevelsSettingsFactory::LEVEL_UP_ANNOUNCEMENT) {
            LevelsSettingsFactory::actOnLevelUpAnnouncementCommand($interaction, $discord, $settingsObject);
        } else if ($interaction->data->options->first()->options->first()->name === LevelsSettingsFactory::ROLE_REWARDS) {
            LevelsSettingsFactory::actOnRoleRewardsCommand($interaction, $discord, $settingsObject);
        } else if ($interaction->data->options->first()->options->first()->name === LevelsSettingsFactory::XP_RATE) {
            LevelsSettingsFactory::actOnXPRateCommand($interaction, $discord, $settingsObject);
        }
    }
}