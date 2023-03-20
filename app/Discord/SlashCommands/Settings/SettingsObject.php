<?php

namespace App\Discord\SlashCommands\Settings;

use App\Setting;
use Discord\Parts\Interactions\Interaction;

class SettingsObject implements SettingsObjectInterface
{
    public VCObject $vc;
    public GlobalSettingsObject $global;

    public function __construct(string $json)
    {
        $data = json_decode($json, true);
        $this->global = new GlobalSettingsObject(json_encode($data['global']));
        $this->vc = new VCObject(json_encode($data['vc']));
    }

    public function jsonSerialize(): array
    {
        $result = [];
        $result['global'] = $this->global;
        $result['vc'] = $this->vc;
        return $result;
    }

    public static function getFromGuildId(string $guildId): ?self
    {
        $settingRow = Setting::where('guild_id', $guildId)->first();

        if (!is_null($settingRow)) {
            return new self($settingRow->object);
        }

        return null;
    }

    public static function getFromInteractionOrGetDefault(Interaction $interaction, ?bool $returnAsArrayWithModel = false): self|array
    {
        $settingsModel = Setting::where('guild_id', $interaction->guild_id)->first();

        if (!is_null($settingsModel)) {
            $settingsObject = new SettingsObject($settingsModel->object);
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