<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use App\Discord\SlashCommands\Settings\Objects\Levels\LevelUpAnnouncementObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\NoXPChannelsObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\NoXPRolesObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\RoleRewardsObject;
use App\Discord\SlashCommands\Settings\Objects\Levels\XPRateObject;

class LevelsObject implements SettingsObjectInterface
{
    public bool $active;
    public LevelUpAnnouncementObject $levelUpAnnouncement;
    public RoleRewardsObject $roleRewards;
    public XPRateObject $xpRate;
    public NoXPRolesObject $noXPRoles;
    public NoXPChannelsObject $noXPChannels;

    public function __construct(array $json)
    {
        $this->active = $json['active'] ?? false;
        $this->levelUpAnnouncement = new LevelUpAnnouncementObject($json['levelUpAnnouncement'] ?? []);
        $this->roleRewards = new RoleRewardsObject($json['roleRewards'] ?? []);
        $this->xpRate = new XPRateObject($json['xpRate'] ?? []);
        $this->noXPRoles = new NoXPRolesObject($json['noXPRoles'] ?? []);
        $this->noXPChannels = new NoXPChannelsObject($json['noXPChannels'] ?? []);
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['active'] = $this->active;
        $result['levelUpAnnouncement'] = $this->levelUpAnnouncement;
        $result['roleRewards'] = $this->roleRewards;
        $result['xpRate'] = $this->xpRate;
        $result['noXPRoles'] = $this->noXPRoles;
        $result['noXPChannels'] = $this->noXPChannels;
        return $result;
    }
}