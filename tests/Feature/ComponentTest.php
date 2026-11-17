<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * <x-atrium-faq::questions /> puts questions on any page of the site.
 */
final class ComponentTest extends TestCase
{
    private Category $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->delivery = $this->category('Delivery', 'delivery', ['sort' => 100]);
        $payment = $this->category('Payment', 'payment', ['sort' => 200]);

        $this->question($this->delivery, 'How long does delivery take?', '<p>Two to three days.</p>');
        $this->question($payment, 'Can I pay by card?', '<p>Yes.</p>');
    }

    public function test_one_category_by_its_code_or_id(): void
    {
        foreach (['category="delivery"', 'category="'.$this->delivery->id.'"', ':category="$category"'] as $attribute) {
            $html = Blade::render('<x-atrium-faq::questions '.$attribute.' />', ['category' => $this->delivery]);

            $this->assertStringContainsString('<summary>How long does delivery take?</summary>', $html);
            $this->assertStringNotContainsString('Can I pay by card?', $html);
            $this->assertSame(['How long does delivery take?'], array_column($this->jsonLd($html)['mainEntity'], 'name'));
        }
    }

    public function test_every_category_when_none_is_given(): void
    {
        $html = Blade::render('<x-atrium-faq::questions />');

        $this->assertSame(['How long does delivery take?', 'Can I pay by card?'], array_column($this->jsonLd($html)['mainEntity'], 'name'));
    }

    public function test_the_structured_data_may_be_left_out(): void
    {
        $html = Blade::render('<x-atrium-faq::questions category="delivery" :schema="false" />');

        $this->assertStringContainsString('How long does delivery take?', $html);
        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_an_unknown_or_inactive_category_draws_nothing(): void
    {
        $this->category('Archive', 'archive', ['active' => false]);

        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions category="nothing" />')));
        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions category="archive" />')));
    }

    public function test_a_code_is_looked_up_in_the_language_of_the_page_then_in_the_default_one(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $this->delivery->update(['name' => ['en' => 'Delivery', 'de' => 'Lieferung'], 'code' => ['en' => 'delivery', 'de' => 'lieferung']]);
        $this->question($this->delivery, 'Is it free?', '<p>Yes.</p>', [
            'question' => ['en' => 'Is it free?', 'de' => 'Ist sie kostenlos?'],
            'answer' => ['en' => '<p>Yes.</p>', 'de' => '<p>Ja.</p>'],
        ]);

        app()->setLocale('de');

        // The code of the page's own language, and the default language's one.
        foreach (['lieferung', 'delivery'] as $code) {
            $html = Blade::render('<x-atrium-faq::questions category="'.$code.'" />');

            $this->assertStringContainsString('<summary>Ist sie kostenlos?</summary>', $html, $code);
            $this->assertStringNotContainsString('Can I pay by card?', $html, $code);
        }

        // Only the questions written in German are shown: the English-only one is not.
        $this->assertStringNotContainsString('How long does delivery take?', Blade::render('<x-atrium-faq::questions category="lieferung" />'));
        // A code of no language the site has finds nothing.
        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions category="livraison" />')));
    }

    public function test_an_id_of_no_category_draws_nothing(): void
    {
        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions category="999999" />')));
        $this->assertSame('', trim(Blade::render('<x-atrium-faq::questions :category="999999" />')));
    }
}
