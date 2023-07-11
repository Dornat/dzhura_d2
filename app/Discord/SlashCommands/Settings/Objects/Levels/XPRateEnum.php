<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

enum XPRateEnum: int
{
    case X025 = 25;
    case X050 = 50;
    case X075 = 75;
    case X1 = 100;
    case X150 = 150;
    case X200 = 200;
    case X250 = 250;
    case X300 = 300;
}
