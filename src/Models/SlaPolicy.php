<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlaPolicy extends Model
{
    use SoftDeletes;

    protected $table = 'service_desk_sla_policies';

    protected $fillable = [
        'name',
        'description',
        'business_hours_schedule_id',
        'conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function businessHoursSchedule(): BelongsTo
    {
        return $this->belongsTo(BusinessHoursSchedule::class, 'business_hours_schedule_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SlaTarget::class, 'sla_policy_id');
    }

    public function escalationRules(): HasMany
    {
        return $this->hasMany(EscalationRule::class, 'sla_policy_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
