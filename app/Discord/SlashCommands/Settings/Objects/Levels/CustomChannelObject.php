<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class CustomChannelObject implements SettingsObjectInterface
{
    public string $id;

    public string $name;

    public function __construct(array $json)
    {
        $this->id = $json['id'] ?? '';
        $this->name = $json['name'] ?? 'База Азова?';
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['id'] = $this->id;
        $result['name'] = $this->name;
        return $result;
    }
}