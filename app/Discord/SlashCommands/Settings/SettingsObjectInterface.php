<?php

namespace App\Discord\SlashCommands\Settings;

use JsonSerializable;

interface SettingsObjectInterface extends JsonSerializable
{
    public function __construct(string $json);
}