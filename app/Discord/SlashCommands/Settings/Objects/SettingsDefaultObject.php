<?php

namespace App\Discord\SlashCommands\Settings\Objects;

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
            ]
        );
    }
}
