<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }
}
