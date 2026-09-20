<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\ServiceDesk\Concerns\HasActorSnapshot;

/**
 * @property int $id
 * @property int $ticket_id
 * @property string $watcher_type
 * @property int $watcher_id
 * @property string|null $watcher_name
 * @property string|null $watcher_email
 * @property Carbon|null $created_at
 * @property-read Ticket $ticket
 * @property-read Model|\Eloquent $watcher
 */
class TicketWatcher extends Model
{
    use HasActorSnapshot;

    public $timestamps = false;

    protected $table = 'service_desk_ticket_watchers';

    protected $fillable = [
        'ticket_id',
        'watcher_type',
        'watcher_id',
        'watcher_name',
        'watcher_email',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketWatcher $watcher) {
            if (empty($watcher->created_at)) {
                $watcher->created_at = now();
            }
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return MorphTo<Model, $this> */
    public function watcher(): MorphTo
    {
        return $this->morphTo('watcher');
    }

    /**
     * The watcher, if its class still exists in this app -- null (never a
     * fatal error) when it doesn't. See HasActorSnapshot.
     */
    public function resolvedWatcher(): ?Model
    {
        return $this->resolveActor('watcher', 'watcher_type');
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string}> */
    protected function actorSnapshots(): array
    {
        return [
            ['watcher_type', 'watcher_id', 'watcher_name', 'watcher_email'],
        ];
    }
}
