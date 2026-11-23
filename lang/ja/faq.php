<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'FAQのセクション',
        'questions' => 'FAQの質問',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'よくある質問',
    'create' => [
        'category' => 'カテゴリーを追加',
        'question' => '質問を追加',
    ],
    'fields' => [
        'category' => 'カテゴリー',
        'question' => '質問',
        'answer' => '回答',
    ],
];
