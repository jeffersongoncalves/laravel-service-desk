<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ticket_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property int $rating
 * @property string|null $comment
 * @property Carbon $created_at
 * @property-read Ticket $ticket
 * @property-read Model|\Eloquent|null $user
 */
class TicketFeedback extends Model
{
    public $timestamps = false;

    protected $table = 'service_desk_ticket_feedback';

    protected $fillable = [
        'ticket_id',
        'user_type',
        'user_id',
        'rating',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return MorphTo<Model, $this> */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }
}
