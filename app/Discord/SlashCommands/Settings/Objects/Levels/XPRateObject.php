<?php

namespace App\Discord\SlashCommands\Settings\Objects\Levels;

use App\Discord\SlashCommands\Settings\Objects\SettingsObjectInterface;

class XPRateObject implements SettingsObjectInterface
{
    public XPRateEnum $rate;

    /**
     * @var array {roleId: string => rate: int}
     */
    public array $roleSpecificRate;

    public function __construct(array $json)
    {
        $this->rate = XPRateEnum::tryFrom($json['rate'] ?? XPRateEnum::X1->value);
        $this->roleSpecificRate = $json['roleSpecificRate'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['rate'] = $this->rate->value;
        $result['roleSpecificXPRate'] = $this->roleSpecificRate;
        return $result;
    }
}