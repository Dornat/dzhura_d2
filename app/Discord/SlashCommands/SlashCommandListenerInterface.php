<?php

namespace App\Discord\SlashCommands;

use Closure;

interface SlashCommandListenerInterface
{
    public function listen(): Closure;
}
