<?php

use Discord\Parts\Interactions\Command\Option;

return [
    [
        'name' => 'settings',
        'description' => 'Налаштування бота'
    ],
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
    ],
    [
        'name' => 'voicecreate',
        'description' => 'Створити голосовий канал',
        'options' => [
            [
                'name' => 'name',
                'description' => 'Назва голосового каналу',
                'type' => Option::STRING,
                'required' => true
            ],
            [
                'name' => 'user_limit',
                'description' => 'Кількість учасників',
                'type' => Option::INTEGER,
                'required' => true
            ],
            [
                'name' => 'category',
                'description' => 'Повна назва категорії, до якої додати голосовий канал',
                'type' => Option::STRING,
                'required' => false
            ],
            [
                'name' => 'lfg_id',
                'description' => 'Ідендифікатор групи (ID), до якого можна прив\'язати голосовий канал',
                'type' => Option::STRING,
                'required' => false
            ],
        ]
    ],
    [
        'name' => 'voicedelete',
        'description' => 'Видалити голосовий канал',
        'options' => [
            [
                'name' => 'id',
                'description' => 'Ідентифікатор голосового каналу',
                'type' => Option::STRING,
                'required' => false
            ]
        ]
    ]
];
