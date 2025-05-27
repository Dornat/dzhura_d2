<?php

namespace App\Discord\SlashCommands\ActionMaps;

use App\Discord\SlashCommands\Settings\Factories\GlobalSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\HelldiversSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\LevelsSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\LfgSettingsFactory;
use App\Discord\SlashCommands\Settings\Factories\VoiceCreateSettingsFactory;

/**
 * @see GlobalSettingsFactory::actOnGlobalTimezoneSelect()
 * @see GlobalSettingsFactory::actOnGlobalSettingsModalOpenBtn()
 * @see VoiceCreateSettingsFactory::actOnVoiceCreateSettingsModalOpenBtn()
 * @see VoiceCreateSettingsFactory::actOnVoiceCreateSettingsModalSubmit()
 * @see LfgSettingsFactory::actOnRolesToTagSelect()
 * @see LfgSettingsFactory::actOnIsRolesToTagActiveSelect()
 * @see LevelsSettingsFactory::actOnActivateSelect()
 * @see LevelsSettingsFactory::actOnAnnouncementChannelSelect()
 * @see LevelsSettingsFactory::actOnCustomChannelSelect()
 * @see LevelsSettingsFactory::actOnCustomizeAnnouncementMessageBtn()
 * @see LevelsSettingsFactory::actOnRoleRewardsTypeSelect()
 * @see LevelsSettingsFactory::actOnRemoveRoleRewardsOnDemotionSelect()
 * @see LevelsSettingsFactory::actOnRoleRewardsLevelNumberSelect()
 * @see LevelsSettingsFactory::actOnRoleRewardsLevelRoleSelect()
 * @see LevelsSettingsFactory::actOnRoleRewardsBtnClear()
 * @see LevelsSettingsFactory::actOnXPRateSelect()
 * @see LevelsSettingsFactory::actOnXPRateRoleSelect()
 * @see LevelsSettingsFactory::actOnXPRateRoleRateSelect()
 * @see LevelsSettingsFactory::actOnXPRateRoleRateBtnClear()
 * @see LevelsSettingsFactory::actOnNoXPRolesConditionSelect()
 * @see LevelsSettingsFactory::actOnNoXPRolesListSelect()
 * @see LevelsSettingsFactory::actOnNoXPRolesListBtnClear()
 * @see LevelsSettingsFactory::actOnNoXPChannelsConditionSelect()
 * @see LevelsSettingsFactory::actOnNoXPChannelsListSelect()
 * @see LevelsSettingsFactory::actOnNoXPChannelsListBtnClear()
 * @see HelldiversSettingsFactory::actOnHelldiversSettingsModalOpenBtn()
 * @see HelldiversSettingsFactory::actOnPermittedRolesSelect()
 * @see HelldiversSettingsFactory::actOnRacesRolesSelect()
 * @see HelldiversSettingsFactory::actOnLevelsRolesSelect()
 */
class SettingsSlashCommandActionMap implements ActionMapInterface
{

    public static function list(): array
    {
        return [
            // GLOBAL
            GlobalSettingsFactory::SETTINGS_GLOBAL_TIMEZONE_SELECT => [
                'factory' => GlobalSettingsFactory::class,
                'method' => 'actOnGlobalTimezoneSelect',
            ],
            GlobalSettingsFactory::SETTINGS_GLOBAL_OPEN_MODAL_BTN => [
                'factory' => GlobalSettingsFactory::class,
                'method' => 'actOnGlobalSettingsModalOpenBtn',
            ],

            // Voice Create
            VoiceCreateSettingsFactory::SETTINGS_VC_OPEN_MODAL_BTN => [
                'factory' => VoiceCreateSettingsFactory::class,
                'method' => 'actOnVoiceCreateSettingsModalOpenBtn',
            ],

            // LFG
            LfgSettingsFactory::LFG_SETTINGS_ROLES_TO_TAG_SELECT => [
                'factory' => LfgSettingsFactory::class,
                'method' => 'actOnRolesToTagSelect',
            ],
            LfgSettingsFactory::LFG_SETTINGS_IS_ROLES_TO_TAG_ACTIVE_SELECT => [
                'factory' => LfgSettingsFactory::class,
                'method' => 'actOnIsRolesToTagActiveSelect',
            ],

            // Levels
            LevelsSettingsFactory::ACTIVATE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnActivateSelect',
            ],
            LevelsSettingsFactory::ANNOUNCEMENT_CHANNEL_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnAnnouncementChannelSelect',
            ],
            LevelsSettingsFactory::CUSTOM_CHANNEL_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnCustomChannelSelect',
            ],
            LevelsSettingsFactory::CUSTOMIZE_ANNOUNCEMENT_MESSAGE_BTN => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnCustomizeAnnouncementMessageBtn',
            ],
            LevelsSettingsFactory::ROLE_REWARDS_TYPE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRoleRewardsTypeSelect',
            ],
            LevelsSettingsFactory::REMOVE_ROLE_REWARDS_ON_DEMOTION_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRemoveRoleRewardsOnDemotionSelect',
            ],
            LevelsSettingsFactory::ROLE_REWARDS_LEVEL_NUMBER_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRoleRewardsLevelNumberSelect',
            ],
            LevelsSettingsFactory::ROLE_REWARDS_LEVEL_ROLE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRoleRewardsLevelRoleSelect',
            ],
            LevelsSettingsFactory::ROLE_REWARDS_BTN_CLEAR => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRoleRewardsBtnClear',
            ],
            LevelsSettingsFactory::XP_RATE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnXPRateSelect',
            ],
            LevelsSettingsFactory::XP_RATE_ROLE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnXPRateRoleSelect',
            ],
            LevelsSettingsFactory::XP_RATE_ROLE_RATE_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnXPRateRoleRateSelect',
            ],
            LevelsSettingsFactory::XP_RATE_ROLE_RATE_BTN_CLEAR => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnXPRateRoleRateBtnClear',
            ],
            LevelsSettingsFactory::NO_XP_ROLES_CONDITION_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPRolesConditionSelect',
            ],
            LevelsSettingsFactory::NO_XP_ROLES_LIST_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPRolesListSelect',
            ],
            LevelsSettingsFactory::NO_XP_ROLES_LIST_BTN_CLEAR => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPRolesListBtnClear',
            ],
            LevelsSettingsFactory::NO_XP_CHANNELS_CONDITION_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPChannelsConditionSelect',
            ],
            LevelsSettingsFactory::NO_XP_CHANNELS_LIST_SELECT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPChannelsListSelect',
            ],
            LevelsSettingsFactory::NO_XP_CHANNELS_LIST_BTN_CLEAR => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPChannelsListBtnClear',
            ],

            // Helldivers
            HelldiversSettingsFactory::SETTINGS_HD_OPEN_MODAL_BTN => [
                'factory' => HelldiversSettingsFactory::class,
                'method' => 'actOnHelldiversSettingsModalOpenBtn',
            ],
            HelldiversSettingsFactory::SETTINGS_HD_PERMITTED_ROLES_SELECT => [
                'factory' => HelldiversSettingsFactory::class,
                'method' => 'actOnPermittedRolesSelect',
            ],
            HelldiversSettingsFactory::SETTINGS_HD_RACES_ROLES_SELECT => [
                'factory' => HelldiversSettingsFactory::class,
                'method' => 'actOnRacesRolesSelect',
            ],
            HelldiversSettingsFactory::SETTINGS_HD_LEVELS_ROLES_SELECT => [
                'factory' => HelldiversSettingsFactory::class,
                'method' => 'actOnLevelsRolesSelect',
            ],
        ];
    }
}
