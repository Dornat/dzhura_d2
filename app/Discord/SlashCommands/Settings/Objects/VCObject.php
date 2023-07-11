<?php

namespace App\Discord\SlashCommands\Settings\Objects;

class VCObject implements SettingsObjectInterface
{
    public array $permittedRoles;
    public string $defaultCategory;
    public int $channelLimit;

    public function __construct(array $json)
    {
        $this->permittedRoles = $json['permittedRoles'] ?? [];
        $this->defaultCategory = $json['defaultCategory'] ?? 'База Азова';
        $this->channelLimit = $json['channelLimit'] ?? 1;
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