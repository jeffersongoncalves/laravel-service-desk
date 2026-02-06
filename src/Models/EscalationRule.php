<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\ServiceDesk\Enums\EscalationAction;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;

/**
 * @property int $id
 * @property int $sla_policy_id
 * @property \JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType $breach_type
 * @property string $trigger_type
 * @property int $minutes_before
 * @property \JeffersonGoncalves\ServiceDesk\Enums\EscalationAction $action
 * @property array|null $action_config
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\SlaPolicy $slaPolicy
 */
class EscalationRule extends Model
{
    protected $table = 'service_desk_escalation_rules';

    protected $fillable = [
        'sla_policy_id',
        'breach_type',
        'trigger_type',
        'minutes_before',
        'action',
        'action_config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'action_config' => 'array',
        'is_active' => 'boolean',
        'breach_type' => SlaBreachType::class,
        'action' => EscalationAction::class,
    ];

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
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
