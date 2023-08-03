<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use JsonSerializable;

interface SettingsObjectInterface extends JsonSerializable
{
    public function __construct(array $json);
}