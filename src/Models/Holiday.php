<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $schedule_id
 * @property string $name
 * @property Carbon $date
 * @property bool $is_recurring
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BusinessHoursSchedule $schedule
 */
class Holiday extends Model
{
    protected $table = 'service_desk_holidays';

    protected $fillable = [
        'schedule_id',
        'name',
        'date',
        'is_recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /** @return BelongsTo<BusinessHoursSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BusinessHoursSchedule::class, 'schedule_id');
    }
}
