<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Sezioni FAQ',
        'questions' => 'Domande FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'FAQ',
    'create' => [
        'category' => 'Aggiungi una categoria',
        'question' => 'Aggiungi una domanda',
    ],
    'fields' => [
        'category' => 'Categoria',
        'question' => 'Domanda',
        'answer' => 'Risposta',
    ],
];
