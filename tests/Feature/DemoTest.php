<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\Sites;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Faq\Tests\TestCase;

/**
 * The demo questions of `atrium:install --demo`.
 */
final class DemoTest extends TestCase
{
    public function test_the_demo_fills_the_faq_once(): void
    {
        $modules = app(Modules::class);
        $module = $modules->find('alveby/atrium-faq');

        $this->assertNotNull($module);
        $this->assertTrue($modules->seedDemo($module));
        $this->assertSame(3, Category::query()->count());
        $this->assertSame(5, Question::query()->count());

        $this->assertFalse($modules->seedDemo($module));
        $this->assertSame(3, Category::query()->count());
        $this->assertSame(5, Question::query()->count());
    }

    public function test_the_demo_keeps_the_order_the_sites_the_languages_and_the_links_of_the_texts(): void
    {
        app(Modules::class)->seedDemo(app(Modules::class)->find('alveby/atrium-faq'));

        $categories = Category::query()->orderBy('sort')->get();
        $this->assertSame([100, 200, 300], $categories->pluck('sort')->all());
        $this->assertSame(['Delivery', 'Payment', 'Warranty and returns'], $categories->map(fn (Category $c): string => $c->getTranslation('name', 'en'))->all());
        $this->assertSame([2, 1, 2], $categories->map(fn (Category $c): int => $c->questions()->count())->all());
        $this->assertSame([100, 200], $categories[0]->questions()->orderBy('sort')->pluck('sort')->all());

        $default = app(Sites::class)->default()->id;

        foreach ([...Category::query()->get(), ...Question::query()->get()] as $record) {
            $this->assertSame($default, $record->site_id);
            $this->assertTrue($record->active);
        }

        // Every text is written in the three languages.
        foreach (Question::query()->get() as $question) {
            foreach (['en', 'de', 'ru'] as $language) {
                $this->assertNotSame('', trim((string) $question->getTranslation('question', $language, false)), $language);
                $this->assertNotSame('', trim((string) $question->getTranslation('answer', $language, false)), $language);
            }
        }
    }

    public function test_the_demo_does_not_touch_a_faq_that_has_content(): void
    {
        $own = $this->category('Mine', 'mine');
        $this->question($own, 'Own question?', '<p>Own answer.</p>');

        $this->assertFalse(app(Modules::class)->seedDemo(app(Modules::class)->find('alveby/atrium-faq')));
        $this->assertSame(['Mine'], Category::query()->get()->map(fn (Category $c): string => $c->getTranslation('name', 'en'))->all());
        $this->assertSame(1, Question::query()->count());
    }

    public function test_a_faq_of_another_site_counts_as_content(): void
    {
        $outlet = Site::query()->create(['code' => 'outlet', 'name' => 'Outlet', 'domains' => ['outlet.example.test'], 'languages' => ['en'], 'default_language' => 'en', 'active' => true, 'sort' => 20]);
        $this->category('Theirs', 'theirs', ['site_id' => $outlet->id]);

        $this->assertFalse(app(Modules::class)->seedDemo(app(Modules::class)->find('alveby/atrium-faq')));
        $this->assertSame(1, Category::query()->withoutGlobalScopes()->count());
    }

    public function test_the_demo_questions_are_on_the_site_in_every_language(): void
    {
        app(Modules::class)->seedDemo(app(Modules::class)->find('alveby/atrium-faq'));

        $response = $this->get('/faq')
            ->assertOk()
            ->assertSeeInOrder(['Delivery', 'How long does delivery take?', 'Payment', 'Warranty and returns']);

        $this->assertCount(5, $this->jsonLd((string) $response->getContent())['mainEntity']);

        Language::query()->create(['code' => 'ru', 'name' => 'Русский']);

        $this->get('/ru/faq/dostavka')
            ->assertOk()
            ->assertSee('<h1>Доставка</h1>', false)
            ->assertSee('Сколько идёт доставка?');

        $this->actingAs($this->admin(), 'admin');
        $this->get('/admin/faq_questions')->assertOk();
    }
}
