<?php

namespace App\Discord\SlashCommands;

use Closure;
use Discord\Parts\Interactions\Interaction;

interface SlashCommandListenerInterface
{
    public static function act(Interaction $interaction): void;
}
