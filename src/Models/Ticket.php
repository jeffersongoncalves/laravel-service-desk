<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;

/**
 * @property int $id
 * @property string $uuid
 * @property string $reference_number
 * @property int $department_id
 * @property int|null $category_id
 * @property string $user_type
 * @property int $user_id
 * @property string|null $assigned_to_type
 * @property int|null $assigned_to_id
 * @property string $title
 * @property string $description
 * @property \JeffersonGoncalves\ServiceDesk\Enums\TicketStatus $status
 * @property \JeffersonGoncalves\ServiceDesk\Enums\TicketPriority $priority
 * @property string $source
 * @property string|null $email_message_id
 * @property int|null $sla_policy_id
 * @property \Illuminate\Support\Carbon|null $first_response_due_at
 * @property \Illuminate\Support\Carbon|null $next_response_due_at
 * @property \Illuminate\Support\Carbon|null $resolution_due_at
 * @property \Illuminate\Support\Carbon|null $first_responded_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property bool $first_response_breached
 * @property bool $resolution_breached
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $last_replied_at
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $user
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $assignedTo
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Department $department
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\TicketComment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\TicketAttachment> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\TicketHistory> $history
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\TicketWatcher> $watchers
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\SlaPolicy|null $slaPolicy
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\TicketSla|null $ticketSla
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Tag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticle> $linkedArticles
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\ServiceRequest|null $serviceRequest
 */
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_desk_tickets';

    protected $fillable = [
        'uuid',
        'reference_number',
        'department_id',
        'category_id',
        'user_type',
        'user_id',
        'assigned_to_type',
        'assigned_to_id',
        'title',
        'description',
        'status',
        'priority',
        'source',
        'email_message_id',
        'sla_policy_id',
        'first_response_due_at',
        'next_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'first_response_breached',
        'resolution_breached',
        'closed_at',
        'due_at',
        'last_replied_at',
        'metadata',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'metadata' => 'array',
        'first_response_due_at' => 'datetime',
        'next_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'first_response_breached' => 'boolean',
        'resolution_breached' => 'boolean',
        'closed_at' => 'datetime',
        'due_at' => 'datetime',
        'last_replied_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }

            if (empty($ticket->reference_number)) {
                $ticket->reference_number = static::generateReferenceNumber();
            }

            if (empty($ticket->status)) {
                $ticket->status = config('service-desk.ticket.default_status', 'open');
            }

            if (empty($ticket->priority)) {
                $ticket->priority = config('service-desk.ticket.default_priority', 'medium');
            }
        });
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = config('service-desk.ticket.reference_prefix', 'SD');
        $lastTicket = static::withTrashed()->orderByDesc('id')->first();
        $nextNumber = $lastTicket ? $lastTicket->id + 1 : 1;

        return sprintf('%s-%05d', $prefix, $nextNumber);
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    public function assignedTo(): MorphTo
    {
        return $this->morphTo('assignedTo');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TicketWatcher::class, 'ticket_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function ticketSla(): HasOne
    {
        return $this->hasOne(TicketSla::class, 'ticket_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'service_desk_taggables');
    }

    public function linkedArticles()
    {
        return $this->belongsToMany(KbArticle::class, 'service_desk_kb_article_ticket', 'ticket_id', 'article_id')
            ->withTimestamps();
    }

    public function serviceRequest(): HasOne
    {
        return $this->hasOne(ServiceRequest::class, 'ticket_id');
    }

    public function scopeByStatus($query, TicketStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            TicketStatus::Closed->value,
            TicketStatus::Resolved->value,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            TicketStatus::Closed->value,
            TicketStatus::Resolved->value,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Resolved->value,
            ]);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [TicketStatus::Closed, TicketStatus::Resolved]);
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to_id !== null;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast() && $this->isOpen();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
