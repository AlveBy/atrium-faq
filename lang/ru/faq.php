<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Разделы FAQ',
        'questions' => 'Вопросы FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'Вопросы и ответы',
    'create' => [
        'category' => 'Добавить раздел',
        'question' => 'Добавить вопрос',
    ],
    'fields' => [
        'category' => 'Раздел',
        'question' => 'Вопрос',
        'answer' => 'Ответ',
    ],
];
