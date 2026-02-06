<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\ServiceDesk\Enums\DayOfWeek;

/**
 * @property int $id
 * @property int $schedule_id
 * @property \JeffersonGoncalves\ServiceDesk\Enums\DayOfWeek $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\BusinessHoursSchedule $schedule
 */
class BusinessHoursTimeSlot extends Model
{
    protected $table = 'service_desk_business_hours_time_slots';

    protected $fillable = [
        'schedule_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => DayOfWeek::class,
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BusinessHoursSchedule::class, 'schedule_id');
    }
}
