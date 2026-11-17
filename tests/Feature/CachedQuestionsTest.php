<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Cache\RecordCache;
use Atrium\Faq\Faq;
use Atrium\Faq\Models\Question;
use Atrium\Faq\Tests\TestCase;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The questions come from the record cache, and a change to a category
 * reaches them: nothing is stale once the editors save.
 */
final class CachedQuestionsTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function asked(): array
    {
        app(RecordCache::class)->reset();

        return app(Faq::class)->questions()->map(fn (Question $q): string => $q->translated('question'))->all();
    }

    /**
     * How many times the list of questions was read from the table.
     */
    private function listReads(Closure $callback): int
    {
        $reads = 0;
        DB::listen(function ($query) use (&$reads): void {
            if (str_contains($query->sql, 'faq_questions') && str_contains($query->sql, 'order by')) {
                $reads++;
            }
        });

        $callback();

        return $reads;
    }

    public function test_a_warm_list_of_questions_is_not_read_from_the_table_again(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->question($category, 'How long?', '<p>Days.</p>');
        $this->asked();

        $this->assertSame(0, $this->listReads(fn () => $this->asked()));
    }

    public function test_a_saved_category_drops_the_cached_list_of_questions(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->question($category, 'How long?', '<p>Days.</p>');
        $this->asked();

        $category->update(['name' => ['en' => 'Shipping']]);

        $this->assertSame(1, $this->listReads(fn () => $this->asked()));
        $this->assertSame(['How long?'], $this->asked());
    }

    public function test_a_hidden_category_takes_its_questions_off_a_warm_list(): void
    {
        $one = $this->category('One', 'one', ['sort' => 100]);
        $two = $this->category('Two', 'two', ['sort' => 200]);
        $this->question($one, 'In one', '<p>.</p>');
        $this->question($two, 'In two', '<p>.</p>');
        $this->assertSame(['In one', 'In two'], $this->asked());

        $one->update(['active' => false]);

        $this->assertSame(['In two'], $this->asked());
    }

    public function test_a_reordered_category_moves_its_questions_on_a_warm_list(): void
    {
        $one = $this->category('One', 'one', ['sort' => 100]);
        $two = $this->category('Two', 'two', ['sort' => 200]);
        $this->question($one, 'In one', '<p>.</p>');
        $this->question($two, 'In two', '<p>.</p>');
        $this->assertSame(['In one', 'In two'], $this->asked());

        $one->update(['sort' => 300]);

        $this->assertSame(['In two', 'In one'], $this->asked());
    }

    public function test_a_saved_question_shows_on_a_warm_list(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $question = $this->question($category, 'How long?', '<p>Days.</p>');
        $this->assertSame(['How long?'], $this->asked());

        $question->update(['question' => ['en' => 'How soon?']]);

        $this->assertSame(['How soon?'], $this->asked());
    }
}
