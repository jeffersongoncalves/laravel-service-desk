<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Models\BusinessHoursSchedule;

interface BusinessHoursCalculator
{
    public function addBusinessMinutes(Carbon $start, int $minutes, BusinessHoursSchedule $schedule): Carbon;

    public function isBusinessHour(Carbon $dateTime, BusinessHoursSchedule $schedule): bool;

    public function isHoliday(Carbon $date, BusinessHoursSchedule $schedule): bool;
}
