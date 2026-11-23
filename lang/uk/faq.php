<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Розділи FAQ',
        'questions' => 'Питання FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'Питання та відповіді',
    'create' => [
        'category' => 'Додати розділ',
        'question' => 'Додати питання',
    ],
    'fields' => [
        'category' => 'Розділ',
        'question' => 'Питання',
        'answer' => 'Відповідь',
    ],
];
