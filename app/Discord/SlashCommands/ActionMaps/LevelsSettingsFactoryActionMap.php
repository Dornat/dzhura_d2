<?php

namespace App\Discord\SlashCommands\ActionMaps;

use App\Discord\SlashCommands\Settings\Factories\LevelsSettingsFactory;

/**
 * @see LevelsSettingsFactory::actOnActivateCommand()
 * @see LevelsSettingsFactory::actOnLevelUpAnnouncementCommand()
 * @see LevelsSettingsFactory::actOnRoleRewardsCommand()
 * @see LevelsSettingsFactory::actOnXPRateCommand()
 * @see LevelsSettingsFactory::actOnNoXPRolesCommand()
 * @see LevelsSettingsFactory::actOnNoXPChannelsCommand()
 */
class LevelsSettingsFactoryActionMap implements ActionMapInterface
{
    public static function list(): array
    {
        return [
            LevelsSettingsFactory::ACTIVATE => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnActivateCommand'
            ],
            LevelsSettingsFactory::LEVEL_UP_ANNOUNCEMENT => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnLevelUpAnnouncementCommand'
            ],
            LevelsSettingsFactory::ROLE_REWARDS => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnRoleRewardsCommand'
            ],
            LevelsSettingsFactory::XP_RATE => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnXPRateCommand'
            ],
            LevelsSettingsFactory::NO_XP_ROLES => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPRolesCommand'
            ],
            LevelsSettingsFactory::NO_XP_CHANNELS => [
                'factory' => LevelsSettingsFactory::class,
                'method' => 'actOnNoXPChannelsCommand'
            ],
        ];
    }
}
