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
        $this->rate = XPRateEnum::tryFrom($json['rate'] ?? XPRateEnum::X100->value);
        $this->roleSpecificRate = $json['roleSpecificRate'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['rate'] = $this->rate->value;
        $result['roleSpecificRate'] = $this->roleSpecificRate;
        return $result;
    }

    public function roleSpecificRateToString(): string
    {
        $result = '';
        foreach ($this->roleSpecificRate as $role => $rate) {
            $result .= "<@&$role> ➡ **" . XPRateEnum::tryFrom((int)$rate)->label() . "** \n";
        }
        return $result;
    }
}