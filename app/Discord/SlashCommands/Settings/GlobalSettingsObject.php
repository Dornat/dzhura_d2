<?php

namespace App\Discord\SlashCommands\Settings;

class GlobalSettingsObject implements SettingsObjectInterface
{
    public string $timeZone;

    public function __construct(string $json)
    {
        $data = json_decode($json, true);
        $this->timeZone = $data['timeZone'] ?? 'Europe/Kyiv';
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['timeZone'] = $this->timeZone;
        return $result;
    }
}