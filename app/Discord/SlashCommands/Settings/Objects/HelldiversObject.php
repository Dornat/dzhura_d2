<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use App\Discord\SlashCommands\Helldivers\HelldiversVoiceChannelCleaner;

class HelldiversObject implements SettingsObjectInterface
{
    public string $vcCategory;
    public string $vcName;
    public string $vcTagMessage;
    public int $emptyVcTimeout;
    public array $permittedRoles;
    public array $racesRoles;
    public array $levelsRoles;

    public function __construct(array $json)
    {
        $this->vcCategory = $json['vcCategory'] ?? 'Helldivers LFG';
        $this->emptyVcTimeout = $json['emptyVcTimeout'] ?? HelldiversVoiceChannelCleaner::DEFAULT_EMPTY_TIMEOUT;
        $this->vcName = $json['vcName'] ?? 'LFG === {player} ===';
        $this->vcTagMessage = $json['vcTagMessage'] ?? '{race} {level}: {vc}';
        $this->permittedRoles = $json['permittedRoles'] ?? [];
        $this->racesRoles = $json['racesRoles'] ?? [];
        $this->levelsRoles = $json['levelsRoles'] ?? [];
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['vcCategory'] = $this->vcCategory;
        $result['vcName'] = $this->vcName;
        $result['vcTagMessage'] = $this->vcTagMessage;
        $result['emptyVcTimeout'] = $this->emptyVcTimeout;
        $result['permittedRoles'] = $this->permittedRoles;
        $result['racesRoles'] = $this->racesRoles;
        $result['levelsRoles'] = $this->levelsRoles;
        return $result;
    }
}
