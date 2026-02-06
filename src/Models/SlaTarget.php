<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sla_policy_id
 * @property string $priority
 * @property int|null $first_response_time
 * @property int|null $next_response_time
 * @property int|null $resolution_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\SlaPolicy $slaPolicy
 */
class SlaTarget extends Model
{
    protected $table = 'service_desk_sla_targets';

    protected $fillable = [
        'sla_policy_id',
        'priority',
        'first_response_time',
        'next_response_time',
        'resolution_time',
    ];

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }
}
