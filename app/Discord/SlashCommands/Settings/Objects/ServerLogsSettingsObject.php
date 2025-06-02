<?php

namespace App\Discord\SlashCommands\Settings\Objects;

class ServerLogsSettingsObject implements SettingsObjectInterface
{
    public bool $active;
    public string $sendMessagesChannel;

    public function __construct(array $json)
    {
        $this->active = $json['active'] ?? false;
        $this->sendMessagesChannel = $json['sendMessagesChannel'] ?? '';
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['active'] = $this->active;
        $result['sendMessagesChannel'] = $this->sendMessagesChannel;
        return $result;
    }
}
