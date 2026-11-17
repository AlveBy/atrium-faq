<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\View\Components;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Faq\Faq;
use Atrium\Faq\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Questions and answers on any page, with their structured data:
 *
 *     <x-atrium-faq::questions />                     every category
 *     <x-atrium-faq::questions category="delivery" /> one, by code or id
 *     <x-atrium-faq::questions :schema="false" />     without the JSON-LD
 */
final class Questions extends Component
{
    public function __construct(
        public Category|int|string|null $category = null,
        public bool $schema = true,
    ) {}

    public function render(): View
    {
        $faq = app(Faq::class);
        $category = $this->findCategory();
        $questions = $this->category !== null && $category === null
            ? collect()
            : $faq->questions($category === null ? null : [$category]);

        return view('atrium-faq::components.questions', [
            'questions' => $questions,
            'jsonLd' => $this->schema ? $faq->jsonLd($questions) : null,
        ]);
    }

    /**
     * A category given by its code is looked up in the page's language,
     * then in the default one.
     */
    private function findCategory(): ?Category
    {
        if ($this->category === null || $this->category instanceof Category) {
            return $this->category;
        }

        if (is_int($this->category) || ctype_digit($this->category)) {
            return Category::query()->find((int) $this->category);
        }

        foreach ([app()->getLocale(), app(ContentLanguages::class)->default()] as $language) {
            $category = Category::query()->where('code->'.$language, $this->category)->first();

            if ($category !== null) {
                return $category;
            }
        }

        return null;
    }
}
