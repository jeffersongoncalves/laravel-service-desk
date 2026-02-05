<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessHoursSchedule extends Model
{
    protected $table = 'service_desk_business_hours_schedules';

    protected $fillable = [
        'name',
        'description',
        'timezone',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function timeSlots(): HasMany
    {
        return $this->hasMany(BusinessHoursTimeSlot::class, 'schedule_id');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class, 'schedule_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'business_hours_schedule_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
