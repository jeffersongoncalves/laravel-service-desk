<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\ServiceDesk\Concerns\HasActorSnapshot;
use JeffersonGoncalves\ServiceDesk\Concerns\UsesServiceDeskConnection;
use JeffersonGoncalves\ServiceDesk\Enums\HistoryAction;

/**
 * @property int $id
 * @property int $ticket_id
 * @property string|null $performer_type
 * @property int|null $performer_id
 * @property string|null $performer_name
 * @property string|null $performer_email
 * @property HistoryAction $action
 * @property string|null $field
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property-read Ticket $ticket
 * @property-read Model|\Eloquent|null $performer
 */
class TicketHistory extends Model
{
    use HasActorSnapshot, UsesServiceDeskConnection;

    public $timestamps = false;

    protected $table = 'service_desk_ticket_history';

    protected $fillable = [
        'ticket_id',
        'performer_type',
        'performer_id',
        'performer_name',
        'performer_email',
        'action',
        'field',
        'old_value',
        'new_value',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'action' => HistoryAction::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketHistory $history) {
            if (empty($history->created_at)) {
                $history->created_at = now();
            }
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return MorphTo<Model, $this> */
    public function performer(): MorphTo
    {
        return $this->morphTo('performer');
    }

    /**
     * The performer, if its class still exists in this app -- null (never a
     * fatal error) when it doesn't. See HasActorSnapshot.
     */
    public function resolvedPerformer(): ?Model
    {
        return $this->resolveActor('performer', 'performer_type');
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string}> */
    protected function actorSnapshots(): array
    {
        return [
            ['performer_type', 'performer_id', 'performer_name', 'performer_email'],
        ];
    }
}
