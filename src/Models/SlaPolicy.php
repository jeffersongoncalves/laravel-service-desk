<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $business_hours_schedule_id
 * @property array|null $conditions
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read BusinessHoursSchedule|null $businessHoursSchedule
 * @property-read Collection<int, SlaTarget> $targets
 * @property-read Collection<int, EscalationRule> $escalationRules
 * @property-read Collection<int, Ticket> $tickets
 */
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

    /** @return BelongsTo<BusinessHoursSchedule, $this> */
    public function businessHoursSchedule(): BelongsTo
    {
        return $this->belongsTo(BusinessHoursSchedule::class, 'business_hours_schedule_id');
    }

    /** @return HasMany<SlaTarget, $this> */
    public function targets(): HasMany
    {
        return $this->hasMany(SlaTarget::class, 'sla_policy_id');
    }

    /** @return HasMany<EscalationRule, $this> */
    public function escalationRules(): HasMany
    {
        return $this->hasMany(EscalationRule::class, 'sla_policy_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
