<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Faq\Faq;
use Atrium\Faq\Models\Question;
use Atrium\Faq\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Which questions a visitor sees, in what order, and the structured data
 * made of them.
 */
final class FaqTest extends TestCase
{
    /**
     * @return array<string,array{array<string,string>,array<string,string>,bool}>
     */
    public static function texts(): array
    {
        return [
            'both written' => [['en' => 'Question?'], ['en' => '<p>Yes.</p>'], true],
            'no answer in the language' => [['en' => 'Question?'], ['de' => '<p>Ja.</p>'], false],
            'no question in the language' => [['de' => 'Frage?'], ['en' => '<p>Yes.</p>'], false],
            'empty answer' => [['en' => 'Question?'], ['en' => ''], false],
            'answer of spaces only' => [['en' => 'Question?'], ['en' => '   '], false],
            'empty question' => [['en' => ''], ['en' => '<p>Yes.</p>'], false],
            'question of spaces only' => [['en' => " \n "], ['en' => '<p>Yes.</p>'], false],
        ];
    }

    /**
     * @param  array<string,string>  $question
     * @param  array<string,string>  $answer
     */
    #[DataProvider('texts')]
    public function test_a_question_needs_its_question_and_answer_in_the_language(array $question, array $answer, bool $shown): void
    {
        $category = $this->category('Delivery', 'delivery');
        Question::query()->create(['category_id' => $category->id, 'question' => $question, 'answer' => $answer]);

        $this->assertCount($shown ? 1 : 0, app(Faq::class)->questions());
    }

    public function test_the_texts_come_without_the_spaces_around_them(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->question($category, "  Question?\n", "\n<p>Yes.</p>  ");

        $question = app(Faq::class)->questions()->sole();

        $this->assertSame('Question?', $question->translated('question'));
        $this->assertSame('<p>Yes.</p>', $question->translated('answer'));
    }

    public function test_questions_follow_the_order_of_their_categories_then_their_own(): void
    {
        $late = $this->category('Late', 'late', ['sort' => 200]);
        $early = $this->category('Early', 'early', ['sort' => 100]);
        $this->question($late, 'L1', '<p>.</p>', ['sort' => 100]);
        $this->question($early, 'E2', '<p>.</p>', ['sort' => 200]);
        $this->question($early, 'E1', '<p>.</p>', ['sort' => 100]);
        $this->question($late, 'L2', '<p>.</p>', ['sort' => 50]);

        $this->assertSame(['E1', 'E2', 'L2', 'L1'], app(Faq::class)->questions()->map(fn (Question $q): string => $q->translated('question'))->all());
    }

    public function test_only_the_asked_categories_are_read(): void
    {
        $one = $this->category('One', 'one');
        $two = $this->category('Two', 'two');
        $this->question($one, 'In one', '<p>.</p>');
        $this->question($two, 'In two', '<p>.</p>');

        $this->assertSame(['In two'], app(Faq::class)->questions([$two])->map(fn (Question $q): string => $q->translated('question'))->all());
        $this->assertCount(2, app(Faq::class)->questions());
        $this->assertCount(0, app(Faq::class)->questions([]));
    }

    public function test_an_inactive_category_or_question_is_not_asked_for(): void
    {
        $off = $this->category('Off', 'off', ['active' => false]);
        $on = $this->category('On', 'on');
        $this->question($off, 'In an inactive category', '<p>.</p>');
        $this->question($on, 'Inactive', '<p>.</p>', ['active' => false]);

        $this->assertCount(0, app(Faq::class)->questions());
        $this->assertCount(0, app(Faq::class)->questions([$off]));
    }

    public function test_the_structured_data_lists_every_question_and_answer(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->question($category, 'One?', '<p>Yes.</p>');
        $this->question($category, 'Two?', '<p>No.</p>');

        $this->assertSame([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                ['@type' => 'Question', 'name' => 'One?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>Yes.</p>']],
                ['@type' => 'Question', 'name' => 'Two?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>No.</p>']],
            ],
        ], app(Faq::class)->schema(app(Faq::class)->questions()));
    }

    public function test_the_script_keeps_the_text_readable_and_escapes_what_could_break_the_page(): void
    {
        $category = $this->category('Delivery', 'delivery');
        $this->question($category, 'Доставка & возврат?', '<p>See /returns</p>');

        $script = (string) app(Faq::class)->jsonLd(app(Faq::class)->questions());

        // Readable: Cyrillic and slashes as they are.
        $this->assertStringContainsString('Доставка', $script);
        $this->assertStringContainsString('/returns', $script);
        // Safe: "<", ">" and "&" cannot end the tag or start an entity.
        $this->assertStringContainsString(chr(92).'u003C', $script);
        $this->assertStringContainsString(chr(92).'u0026', $script);
        $this->assertStringNotContainsString('<p>', $script);
        $this->assertStringStartsWith('<script type="application/ld+json">', $script);
        $this->assertStringEndsWith('</script>', $script);
        $this->assertSame('Доставка & возврат?', $this->jsonLd($script)['mainEntity'][0]['name']);
    }

    public function test_no_questions_make_no_script(): void
    {
        $this->assertSame('', (string) app(Faq::class)->jsonLd([]));
    }

    public function test_the_service_is_one_for_the_whole_request(): void
    {
        $this->assertSame(app(Faq::class), app(Faq::class));
    }
}
