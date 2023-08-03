<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

enum XPRateEnum: int
{
    case X025 = 25;
    case X050 = 50;
    case X075 = 75;
    case X100 = 100;
    case X150 = 150;
    case X200 = 200;
    case X250 = 250;
    case X300 = 300;

    public function label(): string
    {
        return match($this) {
            self::X025 => 'x0.25',
            self::X050 => 'x0.50',
            self::X075 => 'x0.75',
            self::X100 => 'x1',
            self::X150 => 'x1.5',
            self::X200 => 'x2',
            self::X250 => 'x2.5',
            self::X300 => 'x3',
        };
    }
}
