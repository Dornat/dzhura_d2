<?php

namespace App\Discord\SlashCommands\Settings;

class SettingsDefaultObject
{
    public static function get(): SettingsObject
    {
        return new SettingsObject(
            json_encode(
                [
                    'global' => [
                        'timeZone' => 'Europe/Kyiv'
                    ],
                    'vc' => [
                        'permittedRoles' => [],
                        'defaultCategory' => 'База Азова',
                        'channelLimit' => 1,
                    ]
                ]
            )
        );
    }
}