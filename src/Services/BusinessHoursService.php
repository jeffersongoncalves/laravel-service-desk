<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Contracts\BusinessHoursCalculator;
use JeffersonGoncalves\ServiceDesk\Contracts\SlaCalculator;
use JeffersonGoncalves\ServiceDesk\Models\BusinessHoursSchedule;

class BusinessHoursService implements SlaCalculator, BusinessHoursCalculator
{
    public function calculateDueDate(Carbon $startTime, int $minutes, ?BusinessHoursSchedule $schedule = null): Carbon
    {
        if (! $schedule) {
            $schedule = $this->getDefaultSchedule();
        }

        if (! $schedule) {
            return $startTime->copy()->addMinutes($minutes);
        }

        return $this->addBusinessMinutes($startTime->copy(), $minutes, $schedule);
    }

    public function addBusinessMinutes(Carbon $start, int $minutes, BusinessHoursSchedule $schedule): Carbon
    {
        $current = $start->copy()->setTimezone($schedule->timezone);
        $remainingMinutes = $minutes;

        $timeSlots = $schedule->timeSlots()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        if ($timeSlots->isEmpty()) {
            return $start->copy()->addMinutes($minutes);
        }

        $maxIterations = $minutes + (366 * 24 * 60);
        $iterations = 0;

        while ($remainingMinutes > 0 && $iterations < $maxIterations) {
            $iterations++;

            if ($this->isHoliday($current, $schedule)) {
                $current->addDay()->startOfDay();

                continue;
            }

            $dayOfWeek = $current->dayOfWeek;
            $daySlots = $timeSlots->where('day_of_week', $dayOfWeek)->sortBy('start_time');

            if ($daySlots->isEmpty()) {
                $current->addDay()->startOfDay();

                continue;
            }

            foreach ($daySlots as $slot) {
                if ($remainingMinutes <= 0) {
                    break;
                }

                $slotStart = $current->copy()->setTimeFromTimeString($slot->start_time);
                $slotEnd = $current->copy()->setTimeFromTimeString($slot->end_time);

                if ($current->greaterThanOrEqualTo($slotEnd)) {
                    continue;
                }

                $effectiveStart = $current->greaterThan($slotStart) ? $current->copy() : $slotStart->copy();

                $availableMinutes = (int) $effectiveStart->diffInMinutes($slotEnd);

                if ($availableMinutes <= 0) {
                    continue;
                }

                if ($remainingMinutes <= $availableMinutes) {
                    $current = $effectiveStart->addMinutes($remainingMinutes);
                    $remainingMinutes = 0;
                } else {
                    $remainingMinutes -= $availableMinutes;
                    $current = $slotEnd->copy();
                }
            }

            if ($remainingMinutes > 0) {
                $current->addDay()->startOfDay();
            }
        }

        return $current->setTimezone(config('app.timezone', 'UTC'));
    }

    public function isBusinessHour(Carbon $dateTime, BusinessHoursSchedule $schedule): bool
    {
        $localTime = $dateTime->copy()->setTimezone($schedule->timezone);

        if ($this->isHoliday($localTime, $schedule)) {
            return false;
        }

        $dayOfWeek = $localTime->dayOfWeek;

        $slots = $schedule->timeSlots()
            ->where('day_of_week', $dayOfWeek)
            ->get();

        if ($slots->isEmpty()) {
            return false;
        }

        $currentTime = $localTime->format('H:i:s');

        foreach ($slots as $slot) {
            if ($currentTime >= $slot->start_time && $currentTime < $slot->end_time) {
                return true;
            }
        }

        return false;
    }

    public function isHoliday(Carbon $date, BusinessHoursSchedule $schedule): bool
    {
        $localDate = $date->copy()->setTimezone($schedule->timezone);

        $exists = $schedule->holidays()
            ->where(function ($query) use ($localDate) {
                $query->where(function ($q) use ($localDate) {
                    $q->where('is_recurring', false)
                        ->where('date', $localDate->toDateString());
                })->orWhere(function ($q) use ($localDate) {
                    $q->where('is_recurring', true)
                        ->whereMonth('date', $localDate->month)
                        ->whereDay('date', $localDate->day);
                });
            })
            ->exists();

        return $exists;
    }

    protected function getDefaultSchedule(): ?BusinessHoursSchedule
    {
        $defaultId = config('service-desk.sla.default_business_hours_schedule');

        if ($defaultId) {
            return BusinessHoursSchedule::find($defaultId);
        }

        return BusinessHoursSchedule::active()->default()->first();
    }
}
