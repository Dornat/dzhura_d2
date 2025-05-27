<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use App\Discord\SlashCommands\Helldivers\HelldiversVoiceChannelCleaner;

class HelldiversObject implements SettingsObjectInterface
{
    public string $vcCategory;
    public int $vcLimit;
    public string $vcName;
    public int $emptyVcTimeout;
    public array $permittedRoles;
    public array $racesRoles;
    public array $levelsRoles;

    public function __construct(array $json)
    {
        $this->vcCategory = $json['vcCategory'] ?? 'Helldivers LFG';
        $this->vcLimit = $json['vcLimit'] ?? 1;
        $this->emptyVcTimeout = $json['emptyVcTimeout'] ?? HelldiversVoiceChannelCleaner::DEFAULT_EMPTY_TIMEOUT;
        $this->vcName = $json['vcName'] ?? 'LFG === {player} ===';
        $this->permittedRoles = $json['permittedRoles'] ?? [];
        $this->racesRoles = $json['racesRoles'] ?? [];
        $this->levelsRoles = $json['levelsRoles'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['vcCategory'] = $this->vcCategory;
        $result['vcLimit'] = $this->vcLimit;
        $result['vcName'] = $this->vcName;
        $result['emptyVcTimeout'] = $this->emptyVcTimeout;
        $result['permittedRoles'] = $this->permittedRoles;
        $result['racesRoles'] = $this->racesRoles;
        $result['levelsRoles'] = $this->levelsRoles;
        return $result;
    }
}
