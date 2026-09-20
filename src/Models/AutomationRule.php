<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;

/**
 * @property int $id
 * @property string $name
 * @property AutomationTrigger $trigger_event
 * @property array<int, array{field: string, operator: string, value: mixed}>|null $conditions
 * @property AutomationAction $action
 * @property array<string, mixed>|null $action_config
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AutomationRule extends Model
{
    protected $table = 'service_desk_automation_rules';

    protected $fillable = [
        'name',
        'trigger_event',
        'conditions',
        'action',
        'action_config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'trigger_event' => AutomationTrigger::class,
        'conditions' => 'array',
        'action' => AutomationAction::class,
        'action_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTrigger(Builder $query, AutomationTrigger $trigger): Builder
    {
        return $query->where('trigger_event', $trigger);
    }
}
