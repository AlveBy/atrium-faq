<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'categories' => 'Secciones de la FAQ',
        'questions' => 'Preguntas de la FAQ',
    ],
    // What a visitor reads over the FAQ page; the admin's own name of the module is above.
    'title' => 'Preguntas frecuentes',
    'create' => [
        'category' => 'Añadir una categoría',
        'question' => 'Añadir una pregunta',
    ],
    'fields' => [
        'category' => 'Categoría',
        'question' => 'Pregunta',
        'answer' => 'Respuesta',
    ],
];
