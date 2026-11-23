<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Faq\Tests\TestCase;
use Inertia\Testing\AssertableInertia;
use ReflectionMethod;

/**
 * Editors keep the categories and the questions in the admin, like any
 * other module.
 */
final class AdminTest extends TestCase
{
    public function test_the_installed_module_brings_both_lists(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/faq_categories')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Resource/Index')
                ->where('routeName', 'admin.faq_categories')
                ->where('title', 'FAQ sections'));

        $this->get('/admin/faq_questions')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('title', 'FAQ questions'));
    }

    public function test_the_two_modules_are_not_named_alike_in_any_language(): void
    {
        $locales = array_map('basename', glob(__DIR__.'/../../lang/*', GLOB_ONLYDIR) ?: []);
        $this->assertCount(15, $locales);

        foreach ($locales as $locale) {
            $lines = require __DIR__."/../../lang/{$locale}/faq.php";

            $this->assertNotSame($lines['modules']['questions'], $lines['modules']['categories'], $locale);
        }
    }

    public function test_the_modules_are_sections_and_questions_in_the_menu_and_the_crumbs(): void
    {
        $this->assertSame('FAQ sections', Category::getAdminLabel());
        $this->assertSame('FAQ questions', Question::getAdminLabel());

        app()->setLocale('ru');
        $this->assertSame('Разделы FAQ', Category::getAdminLabel());
        $this->assertSame('Вопросы FAQ', Question::getAdminLabel());
        $this->assertSame('Разделы FAQ', (new Category)->getAdminBreadcrumbs()[0]['label']);
        $this->assertSame('Вопросы и ответы', Category::getPublicLabel(), 'visitors keep the old name');
        $this->assertTrue((new ReflectionMethod(Category::class, 'getPublicLabel'))->isPublic(), 'the theme asks it from outside the model');
    }

    public function test_categories_are_created_edited_and_deleted(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/faq_categories', [
                'name' => ['en' => 'Delivery', 'de' => 'Lieferung'],
                'code' => ['en' => 'delivery', 'de' => 'lieferung'],
                'active' => true,
                'sort' => 10,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('admin.faq_categories.index'));

        $category = Category::query()->sole();
        $this->assertEquals(['en' => 'Delivery', 'de' => 'Lieferung'], $category->getTranslations('name'));
        $this->assertNotNull($category->site_id);

        $this->put("/admin/faq_categories/{$category->id}", ['name' => ['en' => 'Shipping'], 'code' => ['en' => 'shipping'], 'active' => true, 'sort' => 10])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('Shipping', $category->fresh()?->getTranslation('name', 'en'));

        $this->delete("/admin/faq_categories/{$category->id}")->assertRedirect(route('admin.faq_categories.index'));
        $this->assertSame(0, Category::query()->count());
    }

    public function test_questions_are_created_edited_searched_and_deleted(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/faq_questions/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Resource/Create'));

        $this->post('/admin/faq_questions', [
            'category_id' => $category->id,
            'question' => ['en' => 'How long does delivery take?'],
            'answer' => ['en' => '<p>Two to three days.</p>'],
            'active' => true,
            'sort' => 10,
        ])->assertSessionDoesntHaveErrors()->assertRedirect(route('admin.faq_questions.index'));

        $question = Question::query()->sole();
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame('<p>Two to three days.</p>', $question->getTranslation('answer', 'en'));

        $this->get('/admin/faq_questions?search=delivery')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('items.total', 1));
        $this->get('/admin/faq_questions?search=payment')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('items.total', 0));

        $this->put("/admin/faq_questions/{$question->id}", [
            'category_id' => $category->id,
            'question' => ['en' => 'How fast is delivery?'],
            'answer' => ['en' => '<p>Two days.</p>'],
            'active' => false,
            'sort' => 20,
        ])->assertSessionDoesntHaveErrors();

        $question = $question->fresh();
        $this->assertSame('How fast is delivery?', $question?->getTranslation('question', 'en'));
        $this->assertFalse($question?->active);

        $this->delete("/admin/faq_questions/{$question?->id}")->assertRedirect(route('admin.faq_questions.index'));
        $this->assertSame(0, Question::query()->count());
    }

    public function test_a_question_needs_a_category_a_question_and_an_answer(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/faq_questions', ['category_id' => 999, 'question' => ['en' => ''], 'answer' => ['en' => '']])
            ->assertSessionHasErrors(['category_id', 'question.en', 'answer.en']);

        $this->assertSame(0, Question::query()->count());
    }
}
