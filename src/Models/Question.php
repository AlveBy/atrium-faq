<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Models;

use Atrium\Admin\Cache\CachesRecords;
use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Contracts\Cacheable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question and its answer, in a category, written per language.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int $category_id
 * @property bool $active
 * @property int $sort
 */
class Question extends Model implements Administrable, Cacheable
{
    use BelongsToSite, CachesRecords, InteractsWithAdmin, Translatable;

    protected $table = 'faq_questions';

    /** @var array<int,string> */
    protected array $translatable = ['question', 'answer'];

    protected $fillable = ['site_id', 'category_id', 'question', 'answer', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer', 'category_id' => 'integer'];
    }

    /**
     * @return BelongsTo<Category,$this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The text in the current language, without falling back to another
     * one: a visitor gets a question only in the language of the page.
     */
    public function translated(string $attribute): string
    {
        return trim((string) $this->getTranslation($attribute, app()->getLocale(), false));
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-faq::faq.modules.questions');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-faq::faq.create.question');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'faq_questions';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            BelongsToField::make('category_id')->label(__('atrium-faq::faq.fields.category'))->model(Category::class)->required(),
            TextField::make('question')->label(__('atrium-faq::faq.fields.question'))->required(),
            ContentField::make('answer')->label(__('atrium-faq::faq.fields.answer'))->required(),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('question')->label(__('atrium-faq::faq.fields.question'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
