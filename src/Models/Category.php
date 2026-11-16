<?php

declare(strict_types=1);

namespace Atrium\Faq\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of questions ("Delivery", "Payment") of one site. Its name and
 * code are written per language; the code is the address of its page.
 *
 * @property int $id
 * @property int|null $site_id
 * @property bool $active
 * @property int $sort
 */
class Category extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'faq_categories';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'code'];

    protected $fillable = ['site_id', 'name', 'code', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer'];
    }

    /**
     * @return HasMany<Question,$this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-faq::faq.modules.categories');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-faq::faq.create.category');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'faq_categories';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * The theme draws the FAQ list and a category's page with the questions
     * and their structured data.
     */
    public static function themeView(string $page): ?string
    {
        return match ($page) {
            'index' => 'atrium-faq::index',
            'page' => 'atrium-faq::category',
            default => null,
        };
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('faq_categories', 'code'),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
