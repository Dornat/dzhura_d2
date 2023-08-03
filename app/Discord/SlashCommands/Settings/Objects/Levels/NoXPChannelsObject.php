<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class NoXPChannelsObject implements SettingsObjectInterface
{
    /**
     * Allow (true) or deny (false) all channels to gain XP except the ones listed
     * in the $except array.
     */
    public bool $allowAllChannels;

    /**
     * The except array to list channels that is denied or allowed to gain XP.
     */
    public array $except;

    public function __construct(array $json)
    {
        $this->allowAllChannels = $json['allowAllChannels'] ?? true;
        $this->except = $json['except'] ?? [];
    }

    public function conditionLabel(): string
    {
        if ($this->allowAllChannels) {
            return 'Дозволити в усіх каналах отримувати XP (досвід)';
        }
        return 'Заборонити в усіх каналах отримувати XP (досвід)';
    }

    public function exceptLabel(): string
    {
        return implode(
            ', ',
            array_map(
                function ($channel) {
                    return "<#$channel>";
                },
                $this->except
            )
        );
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['allowAllChannels'] = $this->allowAllChannels;
        $result['except'] = $this->except;
        return $result;
    }
}