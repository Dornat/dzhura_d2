<?php

use Discord\Parts\Interactions\Command\Option;

return [
    [
        'name' => 'settings',
        'description' => 'Налаштування бота',
        'options' => [
            [
                'name' => 'global',
                'description' => 'Глобальні налаштування бота',
                'type' => Option::SUB_COMMAND,
            ],
            [
                'name' => 'voicecreate',
                'description' => 'Налаштування команди /voicecreate',
                'type' => Option::SUB_COMMAND,
            ],
            [
                'name' => 'lfg',
                'description' => 'Налаштування команди /lfg',
                'type' => Option::SUB_COMMAND,
            ],
            [
                'name' => 'levels',
                'description' => 'Налаштування команди /levels',
                'type' => Option::SUB_COMMAND_GROUP,
                'options' => [
                    [
                        'name' => 'activate',
                        'description' => 'Активація чи деактивація рівневої системи на сервері',
                        'type' => Option::SUB_COMMAND
                    ],
                    [
                        'name' => 'level-up-announcement',
                        'description' => 'Налаштуванн повідомлення про досягнення нового рівня',
                        'type' => Option::SUB_COMMAND
                    ],
                    [
                        'name' => 'role-rewards',
                        'description' => 'Налаштуванн ролей, що можуть чи не можуть отримувати xp',
                        'type' => Option::SUB_COMMAND
                    ],
                    [
                        'name' => 'xp-rate',
                        'description' => 'Налаштуванн рейту (швидкості) отримання xp користувачами',
                        'type' => Option::SUB_COMMAND
                    ],
                    [
                        'name' => 'no-xp-roles',
                        'description' => 'Налаштуванн рольових обмежень',
                        'type' => Option::SUB_COMMAND
                    ],
                    [
                        'name' => 'no-xp-channels',
                        'description' => 'Налаштуванн обмежень для каналів',
                        'type' => Option::SUB_COMMAND
                    ],
                ]
            ],
            [
                'name' => 'helldivers',
                'description' => 'Налаштування команди /helldivers',
                'type' => Option::SUB_COMMAND,
            ],
        ]
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
        'name' => 'voiceedit',
        'description' => 'Редагувати голосовий канал',
        'options' => [
            [
                'name' => 'id',
                'description' => 'Ідентифікатор голосового каналу',
                'type' => Option::STRING,
                'required' => false
            ]
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
    ],
    [
        'name' => 'levels',
        'description' => 'Команди, пов\'язані з рівневою системою',
        'options' => [
            [
                'name' => 'give-xp',
                'description' => 'Дати xp користувачу (команда доступна для адмінів)',
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'member',
                        'description' => 'Користувач',
                        'type' => Option::USER,
                        'required' => true
                    ],
                    [
                        'name' => 'amount',
                        'description' => 'Кількість',
                        'type' => Option::INTEGER,
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'leaderboard',
                'description' => 'Показати "дошку лідерів"',
                'type' => Option::SUB_COMMAND,
            ],
            [
                'name' => 'rank',
                'description' => 'Отримати власний ранг або ранг користувача',
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'member',
                        'description' => 'Користувач',
                        'type' => Option::USER,
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'remove-xp',
                'description' => 'Відняти xp у користувача (команда доступна для адмінів)',
                'type' => Option::SUB_COMMAND,
                'options' => [
                    [
                        'name' => 'member',
                        'description' => 'Користувач',
                        'type' => Option::USER,
                        'required' => true
                    ],
                    [
                        'name' => 'amount',
                        'description' => 'Кількість',
                        'type' => Option::INTEGER,
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'table',
                'description' => 'Таблиця рівнів і скільки досвіду потрібно',
                'type' => Option::SUB_COMMAND,
            ],
        ]
    ],
    [
        'name' => 'helldivers',
        'description' => 'Команди, пов\'язані з helldivers',
        'options' => [
            [
                'name' => 'lfg',
                'description' => 'Створити lfg',
                'type' => Option::SUB_COMMAND,
            ],
        ],
    ],
    [
        'name' => 'zavala',
        'description' => 'Рандомна фраза від Командира Завали'
    ],
];
