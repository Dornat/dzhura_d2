<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class NoXPRolesObject implements SettingsObjectInterface
{
    /**
     * Allow (true) or deny (false) all roles to gain XP except the ones listed
     * in the $except array.
     */
    public bool $allowAllRoles;

    /**
     * The except array to list roles that is denied or allowed to gain XP.
     */
    public array $except;

    public function __construct(array $json)
    {
        $this->allowAllRoles = $json['allowAllRoles'] ?? true;
        $this->except = $json['except'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['allowAllRoles'] = $this->allowAllRoles;
        $result['except'] = $this->except;
        return $result;
    }
}