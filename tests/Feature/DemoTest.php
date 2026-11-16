<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Modules\Modules;
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
        $this->assertSame(5, Question::query()->count());
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
