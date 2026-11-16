<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

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
}
