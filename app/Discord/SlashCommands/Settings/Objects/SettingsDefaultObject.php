<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use App\Discord\SlashCommands\Helldivers\HelldiversVoiceChannelCleaner;

class SettingsDefaultObject
{
    public static function get(): SettingsObject
    {
        return new SettingsObject(
            [
                'global' => [
                    'timeZone' => 'UTC'
                ],
                'vc' => [
                    'permittedRoles' => [],
                    'defaultCategory' => 'База Азова',
                    'channelLimit' => 1,
                ],
                'levels' => [
                    'active' => false,
                ],
                'lfg' => [
                    'isRolesToTagActive' => false,
                    'rolesToTag' => [],
                ],
                'helldivers' => [
                    'vcCategory' => 'Helldivers LFG',
                    'vcLimit' => 1,
                    'vcName' => 'Група === {player} ===',
                    'emptyVcTimeout' => HelldiversVoiceChannelCleaner::DEFAULT_EMPTY_TIMEOUT,
                    'permittedRoles' => [],
                    'racesRoles' => [],
                    'levelsRoles' => [],
                ],
            ]
        );
    }
}
