<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string $visibility
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\KbCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbCategory> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticle> $articles
 */
class KbCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_desk_kb_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'visibility',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<KbCategory, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'parent_id');
    }

    /** @return HasMany<KbCategory, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(KbCategory::class, 'parent_id');
    }

    /** @return HasMany<KbArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'category_id');
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /** @param Builder<static> $query */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
