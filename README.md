# Atrium FAQ

Questions and answers for [Atrium](https://github.com/AlveBy/atrium): grouped in categories, in every content language, per site, with FAQPage structured data (schema.org JSON-LD) for search engines.

## Install

```bash
composer require alveby/atrium-faq
php artisan migrate
```

Two modules appear in the admin menu:

- **FAQ**: the categories ("Delivery", "Payment"): name, code (the page address), active, sort.
- **FAQ questions**: the question, the answer (a `ContentField`: visual editor, HTML or plain text), the category, active, sort.

Names, codes, questions and answers are written per content language; only the default language is required. Both modules belong to a site: each site has its own categories and questions.

## On the site

With the public theme (`alveby/atrium-theme`) installed, the FAQ has pages of its own, and a "FAQ" item in the site menu:

| Address | Page |
|---|---|
| `/faq` | every category with its questions |
| `/faq/delivery` | one category |
| `/de/faq/lieferung` | the same in another content language |

A question opens its answer on a click (`<details>`). Each page puts the FAQPage structured data of the questions it shows into `<head>`, and the category pages go into `sitemap.xml`.

Only active questions of active categories are shown, by sort; a question appears in a language when both the question and the answer are written in it.

To move the pages, list the categories in `config/atrium.php` → `public_urls` yourself; `false` leaves the FAQ without pages:

```php
'public_urls' => [
    Atrium\Faq\Models\Category::class => ['prefix' => 'help'],
],
```

## On any page

```blade
<x-atrium-faq::questions />                         {{-- every category --}}
<x-atrium-faq::questions category="delivery" />     {{-- one, by code or id --}}
<x-atrium-faq::questions :category="$category" />   {{-- or the model --}}
<x-atrium-faq::questions :schema="false" />         {{-- without the JSON-LD --}}
```

The component prints the questions of the current site and language, and their FAQPage structured data unless told not to. It works without the theme.

## Change the look

```bash
php artisan vendor:publish --tag=atrium-faq-views
php artisan vendor:publish --tag=atrium-faq-lang
```

The views land in `resources/views/vendor/atrium-faq`: `index` (the FAQ page), `category`, `partials/questions` and `components/questions`. The admin labels are in 15 languages; English and Russian are written by hand, the others are machine translations.

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/faq/tests
```

The tests cover the admin forms, the pages in two languages and on two sites, the structured data and the component, on SQLite, MySQL and PostgreSQL.

## Contributing

This repository is a read-only copy of `packages/faq` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
