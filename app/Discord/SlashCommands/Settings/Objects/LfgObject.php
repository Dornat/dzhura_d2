<?php

namespace App\Discord\SlashCommands\Settings\Objects;

class LfgObject implements SettingsObjectInterface
{
    public bool $isRolesToTagActive;
    public array $rolesToTag;

    public function __construct(array $json)
    {
        $this->isRolesToTagActive = $json['isRolesToTagActive'] ?? false;
        $this->rolesToTag = $json['rolesToTag'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['isRolesToTagActive'] = $this->isRolesToTagActive;
        $result['rolesToTag'] = $this->rolesToTag;

        return $result;
    }
}
