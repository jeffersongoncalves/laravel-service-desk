<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KbArticleVersion extends Model
{
    public $timestamps = false;

    protected $table = 'service_desk_kb_article_versions';

    protected $fillable = [
        'article_id',
        'version_number',
        'title',
        'content',
        'excerpt',
        'editor_type',
        'editor_id',
        'change_notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    public function editor(): MorphTo
    {
        return $this->morphTo('editor');
    }
}
