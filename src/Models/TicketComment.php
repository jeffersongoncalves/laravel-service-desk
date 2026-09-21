<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Concerns\HasActorSnapshot;
use JeffersonGoncalves\ServiceDesk\Database\Factories\TicketCommentFactory;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;

/**
 * @property int $id
 * @property string|null $uuid
 * @property int $ticket_id
 * @property string $author_type
 * @property int $author_id
 * @property string|null $author_name
 * @property string|null $author_email
 * @property string $body
 * @property CommentType $type
 * @property bool $is_internal
 * @property string|null $email_message_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Ticket $ticket
 * @property-read Model|\Eloquent $author
 * @property-read Collection<int, TicketAttachment> $attachments
 */
class TicketComment extends Model
{
    /** @use HasFactory<TicketCommentFactory> */
    use HasActorSnapshot, HasFactory, SoftDeletes;

    protected $table = 'service_desk_ticket_comments';

    /** @return Factory<self> */
    protected static function newFactory(): Factory
    {
        return TicketCommentFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (TicketComment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'uuid',
        'ticket_id',
        'author_type',
        'author_id',
        'author_name',
        'author_email',
        'body',
        'type',
        'is_internal',
        'email_message_id',
        'metadata',
    ];

    protected $casts = [
        'type' => CommentType::class,
        'is_internal' => 'boolean',
        'metadata' => 'array',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return MorphTo<Model, $this> */
    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    /**
     * The comment author, if its class still exists in this app -- null
     * (never a fatal error) when it doesn't. See HasActorSnapshot.
     */
    public function resolvedAuthor(): ?Model
    {
        return $this->resolveActor('author', 'author_type');
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string}> */
    protected function actorSnapshots(): array
    {
        return [
            ['author_type', 'author_id', 'author_name', 'author_email'],
        ];
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'comment_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->where('type', CommentType::Reply);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNotes(Builder $query): Builder
    {
        return $query->where('type', CommentType::Note);
    }

    public function isReply(): bool
    {
        return $this->type === CommentType::Reply;
    }

    public function isNote(): bool
    {
        return $this->type === CommentType::Note;
    }

    public function isSystem(): bool
    {
        return $this->type === CommentType::System;
    }

    public function isInternal(): bool
    {
        return $this->is_internal;
    }
}
