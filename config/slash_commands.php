<?php

use Discord\Parts\Interactions\Command\Option;

return [
    [
        'name' => 'zoo',
        'description' => 'Створити групу'
    ],
    [
        'name' => 'zoodelete',
        'description' => 'Видалити групу за ідентифікатором',
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
