<?php

use Discord\Parts\Interactions\Command\Option;

return [
    [
        'name' => 'lfg',
        'description' => 'Створити групу'
    ],
    [
        'name' => 'lfgdelete',
        'description' => 'Видалити групу за ідентифікатором',
        'options' => [
            [
                'name' => 'id',
                'description' => 'Ідентифікатор групи (ID)',
                'type' => Option::STRING,
                'required' => true
            ]
        ]
    ],
    [
        'name' => 'lfgedit',
        'description' => 'Змінити деякі дані в групі за ідентифікатором',
        'options' => [
            [
                'name' => 'id',
                'description' => 'Ідентифікатор групи (ID)',
                'type' => Option::STRING,
                'required' => true
            ]
        ]
    ]
];
