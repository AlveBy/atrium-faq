<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq;

use Atrium\Faq\Models\Category;
use Atrium\Theme\RecordView;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class FaqServiceProvider extends ServiceProvider
{
    /**
     * The models, the /faq page and the migrations come from extra.atrium in
     * composer.json, once `atrium:module:install alveby/atrium-faq` has run.
     */
    public function register(): void
    {
        $this->app->singleton(Faq::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-faq');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-faq');

        Blade::componentNamespace('Atrium\\Faq\\View\\Components', 'atrium-faq');

        // The theme draws these pages (Category::themeView) and gives them
        // the categories; the questions are added here.
        View::composer('atrium-faq::index', function (\Illuminate\View\View $view): void {
            $records = $view->getData()['records'] ?? null;
            $categories = [];
            $views = [];

            if ($records instanceof Paginator) {
                foreach ($records->items() as $record) {
                    if ($record instanceof RecordView && $record->record instanceof Category && $record->url() !== null) {
                        $categories[] = $record->record;
                        $views[$record->record->id] = $record;
                    }
                }
            }

            $questions = $this->app->make(Faq::class)->questions($categories);
            $groups = [];

            foreach ($questions->groupBy('category_id') as $id => $items) {
                $groups[] = ['category' => $views[$id], 'questions' => $items];
            }

            $view->with(['groups' => $groups, 'jsonLd' => $this->app->make(Faq::class)->jsonLd($questions)]);
        });

        View::composer('atrium-faq::category', function (\Illuminate\View\View $view): void {
            $page = $view->getData()['page'] ?? null;
            $questions = $page instanceof RecordView && $page->record instanceof Category
                ? $this->app->make(Faq::class)->questions([$page->record])
                : collect();

            $view->with(['questions' => $questions, 'jsonLd' => $this->app->make(Faq::class)->jsonLd($questions)]);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/atrium-faq'),
            ], 'atrium-faq-views');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-faq'),
            ], 'atrium-faq-lang');
        }
    }
}
