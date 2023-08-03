<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class LevelUpAnnouncementObject implements SettingsObjectInterface
{
    public AnnouncementChannelEnum $channel;
    public string $announcementMessage;

    /**
     * If AnnouncementChannelEnum::CUSTOM_CHANNEL is selected then associated
     * channel should be set here.
     */
    public ?CustomChannelObject $customChannel;

    public function __construct(array $json)
    {
        $this->channel = AnnouncementChannelEnum::tryFrom($json['channel'] ?? AnnouncementChannelEnum::DISABLED->value);
        $this->announcementMessage = $json['announcementMessage'] ?? 'Вітаю {player}, ти щойно досяг {level} рівня!';
        $this->customChannel = isset($json['customChannel']) ? new CustomChannelObject($json['customChannel']) : null;
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['channel'] = $this->channel->value;
        $result['announcementMessage'] = $this->announcementMessage;
        $result['customChannel'] = $this->customChannel;
        return $result;
    }
}