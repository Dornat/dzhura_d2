<?php

namespace App\Discord\SlashCommands\Settings\Objects;

class GlobalSettingsObject implements SettingsObjectInterface
{
    public string $timeZone;

    public function __construct(array $json)
    {
        $this->timeZone = $json['timeZone'] ?? 'UTC';
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['timeZone'] = $this->timeZone;
        return $result;
    }
}