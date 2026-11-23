<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Раздзелы FAQ',
        'questions' => 'Пытанні FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'Пытанні і адказы',
    'create' => [
        'category' => 'Дадаць раздзел',
        'question' => 'Дадаць пытанне',
    ],
    'fields' => [
        'category' => 'Раздзел',
        'question' => 'Пытанне',
        'answer' => 'Адказ',
    ],
];
