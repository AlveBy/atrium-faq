<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Tests\TestCase;
use Atrium\Theme\Pages;
use Atrium\Theme\RecordView;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\Paginator;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The theme draws the FAQ page and each category's page; both carry the
 * FAQPage structured data of the questions they show.
 */
final class PublicPageTest extends TestCase
{
    private Category $delivery;

    private Category $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->category('Payment', 'payment', ['sort' => 200]);
        $this->delivery = $this->category('Delivery', 'delivery', ['sort' => 100]);
        $hidden = $this->category('Hidden', 'hidden', ['active' => false]);

        $this->question($this->payment, 'Can I pay by card?', '<p>Yes, any card.</p>');
        $this->question($this->delivery, 'Do you deliver abroad?', '<p>To <a href="/countries">these countries</a>.</p>', ['sort' => 200]);
        $this->question($this->delivery, 'How long does delivery take?', '<p>Two to three days.</p>', ['sort' => 100]);
        $this->question($this->delivery, 'A draft', '<p>Not yet.</p>', ['active' => false]);
        $this->question($hidden, 'In a hidden category', '<p>Nobody sees it.</p>');
    }

    public function test_the_faq_page_shows_every_category_with_its_questions_in_order(): void
    {
        $response = $this->get('/faq')
            ->assertOk()
            ->assertSee('<title>FAQ', false)
            ->assertSee('<h1>FAQ</h1>', false)
            ->assertSee('<link rel="canonical" href="'.$this->root().'/faq">', false)
            ->assertSee('<a href="'.$this->root().'/faq/delivery">Delivery</a>', false)
            ->assertSeeInOrder(['Delivery', 'How long does delivery take?', 'Two to three days.', 'Do you deliver abroad?', 'Payment', 'Can I pay by card?'])
            ->assertSee('<summary>How long does delivery take?</summary>', false)
            ->assertDontSee('A draft')
            ->assertDontSee('Hidden')
            ->assertDontSee('In a hidden category');

        $this->assertSame([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                ['@type' => 'Question', 'name' => 'How long does delivery take?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>Two to three days.</p>']],
                ['@type' => 'Question', 'name' => 'Do you deliver abroad?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>To <a href="/countries">these countries</a>.</p>']],
                ['@type' => 'Question', 'name' => 'Can I pay by card?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>Yes, any card.</p>']],
            ],
        ], $this->jsonLd((string) $response->getContent()));

        // The structured data goes into <head>.
        $this->assertLessThan(strpos((string) $response->getContent(), '</head>'), strpos((string) $response->getContent(), 'application/ld+json'));
    }

    public function test_the_site_menu_leads_to_the_faq(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<a href="'.$this->root().'/faq"', false);
    }

    public function test_a_category_page_shows_its_own_questions(): void
    {
        $response = $this->get('/faq/delivery')
            ->assertOk()
            ->assertSee('<h1>Delivery</h1>', false)
            ->assertSee('<link rel="canonical" href="'.$this->root().'/faq/delivery">', false)
            ->assertSee('<a href="'.$this->root().'/faq">FAQ</a>', false)
            ->assertSeeInOrder(['How long does delivery take?', 'Do you deliver abroad?'])
            ->assertDontSee('Can I pay by card?');

        $schema = $this->jsonLd((string) $response->getContent());
        $this->assertSame(['How long does delivery take?', 'Do you deliver abroad?'], array_column($schema['mainEntity'], 'name'));

        $this->get('/faq/hidden')->assertNotFound();
        $this->get('/faq/nothing')->assertNotFound();
    }

    public function test_a_category_without_questions_has_no_structured_data(): void
    {
        $this->category('Returns', 'returns');

        $this->get('/faq/returns')
            ->assertOk()
            ->assertSee('Nothing here yet.')
            ->assertDontSee('application/ld+json', false);
    }

    public function test_an_answer_cannot_break_out_of_the_script_tag(): void
    {
        $this->question($this->payment, 'Is it </script><script>alert(1)</script> safe?', '<p>Yes & no.</p>');

        $content = (string) $this->get('/faq/payment')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $content);
        $this->assertSame('Is it </script><script>alert(1)</script> safe?', $this->jsonLd($content)['mainEntity'][1]['name']);
    }

    public function test_each_language_shows_the_questions_written_in_it(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $this->delivery->update(['name' => ['en' => 'Delivery', 'de' => 'Lieferung'], 'code' => ['en' => 'delivery', 'de' => 'lieferung']]);
        $this->question($this->delivery, 'Is it free?', '<p>Yes.</p>', [
            'question' => ['en' => 'Is it free?', 'de' => 'Ist sie kostenlos?'],
            'answer' => ['en' => '<p>Yes.</p>', 'de' => '<p>Ja.</p>'],
        ]);

        $response = $this->get('/de/faq/lieferung')
            ->assertOk()
            ->assertSee('<html lang="de">', false)
            ->assertSee('<h1>Lieferung</h1>', false)
            ->assertSee('Ist sie kostenlos?')
            ->assertDontSee('How long does delivery take?');

        $this->assertSame(['Ist sie kostenlos?'], array_column($this->jsonLd((string) $response->getContent())['mainEntity'], 'name'));

        // Payment has no German name, so no German page.
        $this->get('/de/faq')->assertOk()->assertSee('Lieferung')->assertDontSee('Payment');
    }

    public function test_a_category_without_a_name_in_the_language_hides_its_questions_there(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $this->delivery->update(['name' => ['en' => 'Delivery', 'de' => 'Lieferung'], 'code' => ['en' => 'delivery', 'de' => 'lieferung']]);
        $this->question($this->delivery, 'Is it free?', '<p>Yes.</p>', [
            'question' => ['en' => 'Is it free?', 'de' => 'Ist sie kostenlos?'],
            'answer' => ['en' => '<p>Yes.</p>', 'de' => '<p>Ja.</p>'],
        ]);
        // Written in German, but its category has no German name, so no German page.
        $this->question($this->payment, 'Zahlung?', '<p>Karte.</p>', [
            'question' => ['de' => 'Zahlung?'],
            'answer' => ['de' => '<p>Karte.</p>'],
        ]);

        $response = $this->get('/de/faq')->assertOk()->assertSee('Ist sie kostenlos?')->assertDontSee('Zahlung?');

        $this->assertSame(['Ist sie kostenlos?'], array_column($this->jsonLd((string) $response->getContent())['mainEntity'], 'name'));
        $this->get('/de/faq/payment')->assertNotFound();
    }

    public function test_each_site_has_its_own_questions(): void
    {
        $site = Site::query()->create(['code' => 'second', 'name' => 'Second', 'folder' => 'site2']);
        $returns = $this->category('Returns', 'returns', ['site_id' => $site->id]);
        $this->question($returns, 'Can I return a gift?', '<p>Within a month.</p>');

        $this->get('/site2/faq')
            ->assertOk()
            ->assertSee('Can I return a gift?')
            ->assertDontSee('How long does delivery take?');

        $this->get('/faq')->assertOk()->assertDontSee('Can I return a gift?');
        $this->get('/faq/returns')->assertNotFound();
    }

    public function test_the_sitemap_lists_the_category_pages(): void
    {
        $xml = (string) $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.$this->root().'/faq/delivery</loc>', $xml);
        $this->assertStringNotContainsString('/faq/hidden', $xml);
    }

    #[DefineEnvironment('movedToHelp')]
    public function test_the_site_may_move_the_page(): void
    {
        $this->get('/help/delivery')->assertOk()->assertSee('How long does delivery take?');
        $this->get('/faq/delivery')->assertNotFound();
    }

    #[DefineEnvironment('withoutPages')]
    public function test_the_site_may_leave_the_faq_without_pages(): void
    {
        $this->get('/faq')->assertNotFound();
        $this->get('/faq/delivery')->assertNotFound();
    }

    protected function movedToHelp(Application $app): void
    {
        $app['config']->set('atrium.public_urls.'.Category::class, ['prefix' => 'help']);
    }

    protected function withoutPages(Application $app): void
    {
        $app['config']->set('atrium.public_urls.'.Category::class, false);
    }

    /**
     * @return array<string,array{0:?string}>
     */
    public static function pagesOfNoCategory(): array
    {
        return ['no page at all' => [null], 'a page of another record' => ['question']];
    }

    #[DataProvider('pagesOfNoCategory')]
    public function test_a_category_page_that_is_not_one_of_a_category_has_no_questions(?string $kind): void
    {
        $view = view('atrium-faq::category', ['page' => $kind === null ? null : new RecordView($this->payment->questions()->firstOrFail(), $this->app->make(Pages::class))]);

        $this->app['events']->dispatch('composing: atrium-faq::category', [$view]);

        $this->assertCount(0, $view->getData()['questions']);
        $this->assertSame('', (string) $view->getData()['jsonLd']);
    }

    public function test_a_category_page_of_a_category_has_its_questions(): void
    {
        $view = view('atrium-faq::category', ['page' => new RecordView($this->payment, $this->app->make(Pages::class))]);

        $this->app['events']->dispatch('composing: atrium-faq::category', [$view]);

        $this->assertSame(['Can I pay by card?'], $view->getData()['questions']->map(fn ($q) => $q->getTranslation('question', 'en'))->all());
        $this->assertStringContainsString('Can I pay by card?', (string) $view->getData()['jsonLd']);
    }

    public function test_the_faq_page_ignores_records_that_are_not_pages_of_a_category(): void
    {
        $stranger = new class($this->payment)
        {
            public function __construct(public Category $record) {}

            public function url(): string
            {
                return '/stranger';
            }
        };
        $question = new RecordView($this->payment->questions()->firstOrFail(), $this->app->make(Pages::class));
        $view = view('atrium-faq::index', ['records' => new Paginator([$stranger, $question], 10)]);

        $this->app['events']->dispatch('composing: atrium-faq::index', [$view]);

        $this->assertSame([], $view->getData()['groups']);
    }

    public function test_the_theme_draws_the_faq_pages_with_the_package_views(): void
    {
        $this->assertSame('atrium-faq::index', Category::themeView('index'));
        $this->assertSame('atrium-faq::category', Category::themeView('page'));
        $this->assertNull(Category::themeView('other'));
        // Model::__callStatic would hide a protected method, so the contract
        // the theme documents (a public static method) is checked as declared.
        $this->assertTrue((new \ReflectionMethod(Category::class, 'themeView'))->isPublic());
    }
}
