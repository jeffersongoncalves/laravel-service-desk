<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSla extends Model
{
    protected $table = 'service_desk_ticket_sla';

    protected $fillable = [
        'ticket_id',
        'sla_policy_id',
        'priority_at_assignment',
        'first_response_due_at',
        'next_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'first_response_breached',
        'next_response_breached',
        'resolution_breached',
        'paused_minutes',
        'paused_at',
    ];

    protected $casts = [
        'first_response_due_at' => 'datetime',
        'next_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'first_response_breached' => 'boolean',
        'next_response_breached' => 'boolean',
        'resolution_breached' => 'boolean',
        'paused_minutes' => 'integer',
        'paused_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function pause(): void
    {
        if ($this->isPaused()) {
            return;
        }

        $this->update([
            'paused_at' => Carbon::now(),
        ]);
    }

    public function resume(): void
    {
        if (! $this->isPaused()) {
            return;
        }

        $pausedMinutes = (int) $this->paused_at->diffInMinutes(Carbon::now());

        $this->update([
            'paused_minutes' => $this->paused_minutes + $pausedMinutes,
            'paused_at' => null,
        ]);
    }

    public function recordFirstResponse(): void
    {
        $this->update([
            'first_responded_at' => Carbon::now(),
        ]);
    }

    public function recordResolution(): void
    {
        $this->update([
            'resolved_at' => Carbon::now(),
        ]);
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }
}
