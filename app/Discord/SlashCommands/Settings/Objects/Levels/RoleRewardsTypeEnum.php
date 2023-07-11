<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

enum RoleRewardsTypeEnum: int
{
    case STACK_PREVIOUS_REWARDS = 1;
    case REMOVE_PREVIOUS_REWARDS = 2;

    public function label(): string
    {
        return RoleRewardsTypeEnum::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            RoleRewardsTypeEnum::STACK_PREVIOUS_REWARDS => 'Додавати нові ролі до вже отриманих',
            RoleRewardsTypeEnum::REMOVE_PREVIOUS_REWARDS => 'Прибирати старі ролі',
        };
    }
}
