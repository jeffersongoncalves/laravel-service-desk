<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\ServiceDesk\Enums\DayOfWeek;

/**
 * @property int $id
 * @property int $schedule_id
 * @property DayOfWeek $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BusinessHoursSchedule $schedule
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

    /** @return BelongsTo<BusinessHoursSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BusinessHoursSchedule::class, 'schedule_id');
    }
}
