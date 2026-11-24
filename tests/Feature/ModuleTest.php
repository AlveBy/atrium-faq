<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Faq\Faq;
use Atrium\Faq\FaqServiceProvider;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Faq\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the package says about itself: the admin forms and lists, the view
 * the theme draws a category with, the files it lets an application publish.
 */
final class ModuleTest extends TestCase
{
    /**
     * @return array<string,array{string,array<string,mixed>}>
     */
    public static function defaults(): array
    {
        return [
            'categories' => ['faq_categories', ['active' => true, 'sort' => 500]],
            'questions' => ['faq_questions', ['active' => true, 'sort' => 500]],
        ];
    }

    /**
     * @param  array<string,mixed>  $expected
     */
    #[DataProvider('defaults')]
    public function test_a_new_record_is_active_and_comes_after_the_default_sort(string $route, array $expected): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/{$route}/create")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($expected): void {
                $fields = collect($page->toArray()['props']['fields'])->keyBy('name');

                foreach ($expected as $name => $default) {
                    $this->assertSame($default, $fields[$name]['default'], $name);
                }
            });
    }

    /**
     * @return array<string,array{string,array<int,string>}>
     */
    public static function columns(): array
    {
        return [
            'categories' => ['faq_categories', ['id', 'name', 'sort', 'active']],
            'questions' => ['faq_questions', ['id', 'question', 'sort', 'active']],
        ];
    }

    /**
     * @param  array<int,string>  $names
     */
    #[DataProvider('columns')]
    public function test_the_list_starts_with_a_sortable_id(string $route, array $names): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/{$route}")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($names): void {
                $columns = $page->toArray()['props']['columns'];

                $this->assertSame($names, array_column($columns, 'name'));
                $this->assertTrue($columns[0]['sortable']);
            });
    }

    /**
     * @return array<string,array{string}>
     */
    public static function routes(): array
    {
        return ['categories' => ['faq_categories'], 'questions' => ['faq_questions']];
    }

    #[DataProvider('routes')]
    public function test_the_admin_is_closed_to_a_visitor(string $route): void
    {
        $this->get("/admin/{$route}")->assertRedirect();
        $this->get("/admin/{$route}/create")->assertRedirect();
    }

    public function test_a_category_is_active_by_default_in_the_database_and_as_a_flag(): void
    {
        $category = $this->category('Delivery', 'delivery');

        $this->assertTrue($category->fresh()?->active);
        $this->assertFalse($this->category('Off', 'off', ['active' => false])->fresh()?->active);
    }

    public function test_the_theme_draws_the_list_and_a_category_with_the_views_of_the_module(): void
    {
        $this->assertSame('atrium-faq::index', Category::themeView('index'));
        $this->assertSame('atrium-faq::category', Category::themeView('page'));
        // Nothing for the pages the module does not have, or does not know.
        $this->assertNull(Category::themeView('section'));
        $this->assertNull(Category::themeView('anything else'));
    }

    public function test_a_category_knows_its_questions_and_a_question_its_category(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $other = $this->category('Payment', 'payment');
        $question = $this->question($category, 'How long?', '<p>Soon.</p>');

        $this->assertSame([$question->id], $category->questions()->pluck('id')->all());
        $this->assertCount(0, $other->questions);
        $this->assertSame($category->id, $question->category?->id);
        $this->assertSame($category->id, $question->category()->sole()->id);
    }

    public function test_the_questions_are_offered_as_a_board_of_categories_kept_in_order(): void
    {
        $views = Question::getAdministrationViews();

        $this->assertCount(1, $views);
        $this->assertSame('kanban', $views[0]->type());
        $this->assertSame('category_id', $views[0]->getField());
        $this->assertSame('question', $views[0]->getTitleField());
        $this->assertSame(['active'], $views[0]->getCards());
        $this->assertSame('sort', $views[0]->getOrder());
    }

    public function test_the_service_and_the_component_are_registered(): void
    {
        $this->assertTrue(app()->isShared(Faq::class));
        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions category="nothing" />')));
        $this->assertArrayHasKey('atrium-faq', app('blade.compiler')->getClassComponentNamespaces());
    }

    public function test_the_views_and_texts_can_be_published(): void
    {
        $views = ServiceProvider::pathsToPublish(FaqServiceProvider::class, 'atrium-faq-views');
        $lang = ServiceProvider::pathsToPublish(FaqServiceProvider::class, 'atrium-faq-lang');

        $this->assertSame([realpath(__DIR__.'/../../resources/views')], array_map('realpath', array_keys($views)));
        $this->assertSame([resource_path('views/vendor/atrium-faq')], array_values($views));
        $this->assertSame([realpath(__DIR__.'/../../lang')], array_map('realpath', array_keys($lang)));
        $this->assertSame([lang_path('vendor/atrium-faq')], array_values($lang));
        $this->assertDirectoryExists(array_key_first($views));
        $this->assertDirectoryExists(array_key_first($lang));
    }
}
