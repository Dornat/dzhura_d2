<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class RoleRewardsObject implements SettingsObjectInterface
{
    public RoleRewardsTypeEnum $roleRewardsType;

    /**
     * @var array {level: int => role: string}
     */
    public array $roleRewards;

    public bool $removeRoleRewardsOnDemotion;

    public function __construct(array $json)
    {
        $this->roleRewardsType = RoleRewardsTypeEnum::tryFrom($json['roleRewardsType'] ?? RoleRewardsTypeEnum::STACK_PREVIOUS_REWARDS->value);
        $this->roleRewards = $json['roleRewards'] ?? [];
        $this->removeRoleRewardsOnDemotion = $json['removeRoleRewardsOnDemotion'] ?? true;
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['roleRewardsType'] = $this->roleRewardsType->value;
        $result['roleRewards'] = $this->roleRewards;
        $result['removeRoleRewardsOnDemotion'] = $this->removeRoleRewardsOnDemotion;
        return $result;
    }

    public function roleRewardsToString(): string
    {
        $result = '';
        ksort($this->roleRewards);
        foreach ($this->roleRewards as $level => $role) {
            $result .= "**$level** ➡ <@&$role>\n";
        }
        return $result;
    }
}