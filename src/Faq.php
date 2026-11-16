<?php

declare(strict_types=1);

namespace Atrium\Faq;

use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * The questions a visitor sees and their structured data for search
 * engines (schema.org FAQPage).
 */
final class Faq
{
    /**
     * Active questions of active categories, in the editors' order: by
     * category, then by sort. Only those asked and answered in the current
     * language.
     *
     * @param  iterable<Category>|null  $categories  every category of the site when null
     * @return Collection<int,Question>
     */
    public function questions(?iterable $categories = null): Collection
    {
        $query = Category::query()->where('active', true)->orderBy('sort')->orderBy('id');

        if ($categories !== null) {
            $query->whereKey(collect($categories)->map(fn (Category $category): mixed => $category->getKey())->all());
        }

        $order = $query->pluck('id')->flip();

        if ($order->isEmpty()) {
            return new Collection;
        }

        return Question::query()
            ->whereIn('category_id', $order->keys())
            ->where('active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->filter(fn (Question $question): bool => $question->translated('question') !== '' && $question->translated('answer') !== '')
            ->sortBy(fn (Question $question): int => (int) $order[$question->category_id], SORT_NUMERIC)
            ->values();
    }

    /**
     * @param  iterable<Question>  $questions
     * @return array<string,mixed>
     */
    public function schema(iterable $questions): array
    {
        $entities = [];

        foreach ($questions as $question) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $question->translated('question'),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $question->translated('answer'),
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * The FAQPage script tag, or nothing when there are no questions. The
     * JSON escapes "<", so an answer cannot close the tag.
     *
     * @param  iterable<Question>  $questions
     */
    public function jsonLd(iterable $questions): HtmlString
    {
        $schema = $this->schema($questions);

        if ($schema['mainEntity'] === []) {
            return new HtmlString('');
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }
}
