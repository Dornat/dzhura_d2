<?php

namespace App\Discord\SlashCommands;

use App\Discord\SlashCommands\ActionMaps\LevelsSettingsFactoryActionMap;
use App\Discord\SlashCommands\ActionMaps\SettingsSlashCommandActionMap;
use App\Discord\SlashCommands\Settings\Factories\GlobalSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\HelldiversSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\LfgSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\ServerLogsSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\VoiceCreateSettingsFactory;
use App\Discord\SlashCommands\Settings\Objects\SettingsObject;
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
    public const LFG = 'lfg';
    public const HELLDIVERS = 'helldivers';
    public const SERVER_LOGS = 'server-logs';

    public static function act(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->type === InteractionType::APPLICATION_COMMAND && $interaction->data->name === self::SETTINGS) {
            if (!$interaction->member->permissions->administrator) {
                $interaction->respondWithMessage(MessageBuilder::new()->setContent('Щось не схоже, що ти маєш права адміністратора. 👁'), true);
                return;
            }

            /**
             * @see self::actVCResponse()
             * @see self::actLevelsResponse()
             * @see self::actGlobalResponse()
             * @see self::actLfgResponse()
             * @see self::actHelldiversResponse()
             * @see self::actServerLogsResponse()
             */
            $action = match($interaction->data->options->first()->name) {
                self::VC => 'actVCResponse',
                self::LEVELS => 'actLevelsResponse',
                self::LFG => 'actLfgResponse',
                self::HELLDIVERS => 'actHelldiversResponse',
                self::SERVER_LOGS => 'actServerLogsResponse',
                default => 'actGlobalResponse',
            };

            self::{$action}($interaction, $discord);
        } else if ($interaction->type === InteractionType::MESSAGE_COMPONENT && $interaction->data->custom_id === VoiceCreateSettingsFactory::SETTINGS_VC_CREATION_ROLES_SELECT) {
            VoiceCreateSettingsFactory::actOnVCRoleSelect($interaction, $discord);
        } else {
            $actionsMap = SettingsSlashCommandActionMap::list();

            $customId = $interaction->data->custom_id;
            if (isset($actionsMap[$customId])) {
                $factoryName = $actionsMap[$customId]['factory'];
                $methodName = $actionsMap[$customId]['method'];
                $factoryName::{$methodName}($interaction, $discord);
            }
        }
    }

    public static function actGlobalResponse(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $globalSettingsFactory = new GlobalSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Через обмеженість discord API зміна налаштувань через поля селекторів розташовані окремими полями, а введення налаштувань для інших полів винесені в модальні вікна, які можна викликати натиснувши на відповідні кнопки.\n> \n> ⚙")
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
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $voiceCreateFactory = new VoiceCreateSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Через обмеженість discord API зміна налаштувань через поля селекторів розташовані окремими полями, а введення налаштувань для інших полів винесені в модальні вікна, які можна викликати натиснувши на відповідні кнопки.\n> \n> ⚙")
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
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $actionsMap = LevelsSettingsFactoryActionMap::list();

        $customId = $interaction->data->options->first()->options->first()->name;
        if (isset($actionsMap[$customId])) {
            $factoryName = $actionsMap[$customId]['factory'];
            $methodName = $actionsMap[$customId]['method'];
            $factoryName::{$methodName}($interaction, $discord, $settingsObject);
        }
    }

    public static function actLfgResponse(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $lfgSettingsFactory = new LfgSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Тут можна обрати ті ролі, які будуть тегнуті після створення групи.\n> \n> ⚙")
            ->addEmbed(
                $lfgSettingsFactory->embed
            )
            ->setComponents([
                $lfgSettingsFactory->activationSelect,
                $lfgSettingsFactory->roleSelect,
            ]);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actHelldiversResponse(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $helldiversSettingsFactory = new HelldiversSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Тут можна налаштувати команду `/helldivers lfg`.\n> \n> ⚙")
            ->addEmbed(
                $helldiversSettingsFactory->embed
            )
            ->setComponents([
                $helldiversSettingsFactory->permittedRolesSelect,
                $helldiversSettingsFactory->racesRolesSelect,
                $helldiversSettingsFactory->levelsRolesSelect,
                $helldiversSettingsFactory->modalBtnActionRow,
            ]);

        $interaction->respondWithMessage($msg, true);
    }

    public static function actServerLogsResponse(Interaction $interaction, Discord $discord): void
    {
        $settingsObject = SettingsObject::getFromInteractionOrGetDefault($interaction);

        $serverLogsSettingsFactory = new ServerLogsSettingsFactory($discord, $settingsObject);

        $msg = MessageBuilder::new()
            ->setContent("> 📖 Тут можна налаштувати логи для серверу.\n> \n> ⚙")
            ->addEmbed(
                $serverLogsSettingsFactory->embed
            )
            ->setComponents([
                $serverLogsSettingsFactory->activationSelect,
                $serverLogsSettingsFactory->sendMessagesChannelSelect,
            ]);

        $interaction->respondWithMessage($msg, true);
    }
}
