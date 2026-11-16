<?php

declare(strict_types=1);

namespace Atrium\Faq\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Faq\FaqServiceProvider;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            ThemeServiceProvider::class,
            FaqServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Help desk');
        // CI runs the suite against MySQL and PostgreSQL too: the texts are
        // JSON columns, read differently by each.
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', ['alveby/atrium-faq']);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        PublicUrl::forgetPaths();
    }

    /**
     * On MySQL and PostgreSQL the whole suite shares one database, set up by
     * whichever package's tests run first: the FAQ tables come with these.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function category(string $name, string $code, array $attributes = []): Category
    {
        return Category::query()->create(['name' => ['en' => $name], 'code' => ['en' => $code], ...$attributes]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function question(Category $category, string $question, string $answer, array $attributes = []): Question
    {
        return Question::query()->create([
            'category_id' => $category->id,
            'site_id' => $category->site_id,
            'question' => ['en' => $question],
            'answer' => ['en' => $answer],
            ...$attributes,
        ]);
    }

    /**
     * The FAQPage data of a page, decoded.
     *
     * @return array<string,mixed>
     */
    protected function jsonLd(string $html): array
    {
        $this->assertSame(1, preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches), 'One FAQPage script expected');

        return json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);
    }

    protected function root(): string
    {
        return rtrim(url('/'), '/');
    }
}
