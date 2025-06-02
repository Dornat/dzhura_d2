<?php

namespace App\Discord\SlashCommands\Settings\Objects;

use App\Setting;
use Discord\Parts\Interactions\Interaction;

class SettingsObject implements SettingsObjectInterface
{
    public VCObject $vc;
    public GlobalSettingsObject $global;
    public LevelsObject $levels;
    public LfgObject $lfg;
    public HelldiversObject $helldivers;
    public ServerLogsSettingsObject $serverLogs;

    public function __construct(array $json)
    {
        $this->global = new GlobalSettingsObject($json['global'] ?? []);
        $this->vc = new VCObject($json['vc'] ?? []);
        $this->levels = new LevelsObject($json['levels'] ?? []);
        $this->lfg = new LfgObject($json['lfg'] ?? []);
        $this->helldivers = new HelldiversObject($json['helldivers'] ?? []);
        $this->serverLogs = new ServerLogsSettingsObject($json['serverLogs'] ?? []);
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['global'] = $this->global;
        $result['vc'] = $this->vc;
        $result['levels'] = $this->levels;
        $result['lfg'] = $this->lfg;
        $result['helldivers'] = $this->helldivers;
        $result['serverLogs'] = $this->serverLogs;
        return $result;
    }

    public static function getFromGuildId(string $guildId): ?self
    {
        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (!is_null($settingRow)) {
            return new self(json_decode($settingRow->object, true));
        }

        return null;
    }

    public static function getFromInteractionOrGetDefault(Interaction $interaction, ?bool $returnAsArrayWithModel = false): self|array
    {
        $settingsModel = Setting::where('guild_id', $interaction->guild_id)->first();

        if (!is_null($settingsModel)) {
            $settingsObject = new SettingsObject(json_decode($settingsModel->object, true));
        } else {
            $settingsObject = SettingsDefaultObject::get();
            $settingsModel = new Setting();
            $settingsModel->guild_id = $interaction->guild_id;
            $settingsModel->created_by = $interaction->member->user->id;
        }

        if ($returnAsArrayWithModel) {
            return [$settingsObject, $settingsModel];
        }

        return $settingsObject;
    }
}
