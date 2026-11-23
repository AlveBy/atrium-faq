<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Seções do FAQ',
        'questions' => 'Perguntas do FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'Perguntas frequentes',
    'create' => [
        'category' => 'Adicionar categoria',
        'question' => 'Adicionar pergunta',
    ],
    'fields' => [
        'category' => 'Categoria',
        'question' => 'Pergunta',
        'answer' => 'Resposta',
    ],
];
