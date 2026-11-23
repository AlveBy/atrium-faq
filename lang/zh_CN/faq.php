<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => '常见问题分类',
        'questions' => '常见问题条目',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => '常见问题',
    'create' => [
        'category' => '添加分类',
        'question' => '添加问题',
    ],
    'fields' => [
        'category' => '分类',
        'question' => '问题',
        'answer' => '回答',
    ],
];
