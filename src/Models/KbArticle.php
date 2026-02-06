<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Enums\ArticleStatus;
use JeffersonGoncalves\ServiceDesk\Enums\ArticleVisibility;

/**
 * @property int $id
 * @property string $uuid
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string|null $excerpt
 * @property string $author_type
 * @property int $author_id
 * @property \JeffersonGoncalves\ServiceDesk\Enums\ArticleStatus $status
 * @property \JeffersonGoncalves\ServiceDesk\Enums\ArticleVisibility $visibility
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property int $view_count
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int $current_version
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\KbCategory $category
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $author
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticleVersion> $versions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticleFeedback> $feedback
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticle> $relatedArticles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Ticket> $linkedTickets
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Tag> $tags
 */
class KbArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_desk_kb_articles';

    protected $fillable = [
        'uuid',
        'category_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'author_type',
        'author_id',
        'status',
        'visibility',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'published_at',
        'current_version',
        'metadata',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'visibility' => ArticleVisibility::class,
        'published_at' => 'datetime',
        'metadata' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (KbArticle $article) {
            if (empty($article->uuid)) {
                $article->uuid = (string) Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }

    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KbArticleVersion::class, 'article_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(KbArticleFeedback::class, 'article_id');
    }

    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(KbArticle::class, 'service_desk_kb_article_relations', 'article_id', 'related_article_id');
    }

    public function linkedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'service_desk_kb_article_ticket', 'article_id', 'ticket_id')
            ->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'service_desk_taggables');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published);
    }

    public function scopeByVisibility(Builder $query, ArticleVisibility $visibility): Builder
    {
        return $query->where('visibility', $visibility);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
