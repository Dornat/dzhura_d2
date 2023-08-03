<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

enum AnnouncementChannelEnum: int
{
    case DISABLED = 0;
    case CURRENT_CHANNEL = 1;
    case PRIVATE_MESSAGE = 2;
    case CUSTOM_CHANNEL = 3;

    public function label(): string
    {
        return AnnouncementChannelEnum::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            AnnouncementChannelEnum::DISABLED => 'Вимкнено',
            AnnouncementChannelEnum::CURRENT_CHANNEL => 'Поточний канал',
            AnnouncementChannelEnum::PRIVATE_MESSAGE => 'Приватне повідомлення',
            AnnouncementChannelEnum::CUSTOM_CHANNEL => 'Власний канал',
        };
    }
}
