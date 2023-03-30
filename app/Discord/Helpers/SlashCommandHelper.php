<?php

namespace App\Discord\Helpers;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Repository\Interaction\ComponentRepository;

class SlashCommandHelper
{
    public static function assembleAtUsersString(array $userIds): string
    {
        return implode(', ', array_map(function ($id) {
            return "<@$id>";
        }, $userIds));
    }

    public static function constructEmbedActionRowFromComponentRepository(ComponentRepository $componentRepository): ActionRow
    {
        $embedActionRow = ActionRow::new();
        foreach ($componentRepository as $component) {
            $btn = Button::new($component->style, $component->custom_id);
            if ($component->label) {
                $btn->setLabel($component->label);
            }
            if ($component->emoji) {
                $btn->setEmoji($component->emoji);
            }
            if ($component->url) {
                $btn->setUrl($component->url);
            }
            $embedActionRow->addComponent($btn);
        }

        return $embedActionRow;
    }
}
