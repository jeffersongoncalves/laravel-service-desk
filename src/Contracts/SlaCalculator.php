<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Models\BusinessHoursSchedule;

interface SlaCalculator
{
    public function calculateDueDate(Carbon $startTime, int $minutes, ?BusinessHoursSchedule $schedule = null): Carbon;
}
