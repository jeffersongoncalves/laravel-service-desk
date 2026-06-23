<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $article_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property bool $is_helpful
 * @property string|null $comment
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property-read KbArticle $article
 * @property-read Model|\Eloquent|null $user
 */
class KbArticleFeedback extends Model
{
    public $timestamps = false;

    protected $table = 'service_desk_kb_article_feedback';

    protected $fillable = [
        'article_id',
        'user_type',
        'user_id',
        'is_helpful',
        'comment',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<KbArticle, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }
}
