<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
