<?php

namespace App\Discord\SlashCommands;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

interface SlashCommandListenerInterface
{
    public static function act(Interaction $interaction, Discord $discord): void;
}
