<?php

namespace App\Discord\SlashCommands\ActionMaps;

interface ActionMapInterface
{
    /**
     * Return array of action maps that are mapped like this:
     * [
     *   KEY_NAME => [
     *     factory => FactoryName,
     *     method => actMethodName
     *  ]
     * ]
     */
    public static function list(): array;
}
