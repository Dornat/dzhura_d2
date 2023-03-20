<?php

namespace App\Discord\SlashCommands\Settings;

class VCObject implements SettingsObjectInterface
{
    public array $permittedRoles;
    public string $defaultCategory;
    public int $channelLimit;

    public function __construct(string $json)
    {
        $data = json_decode($json, true);
        $this->permittedRoles = $data['permittedRoles'] ?? [];
        $this->defaultCategory = $data['defaultCategory'] ?? 'База Азова';
        $this->channelLimit = $data['channelLimit'] ?? 1;
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['permittedRoles'] = $this->permittedRoles;
        $result['defaultCategory'] = $this->defaultCategory;
        $result['channelLimit'] = $this->channelLimit;
        return $result;
    }
}