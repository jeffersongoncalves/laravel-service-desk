<?php

use Carbon\Carbon;
use JeffersonGoncalves\ServiceDesk\Enums\DayOfWeek;
use JeffersonGoncalves\ServiceDesk\Models\BusinessHoursSchedule;
use JeffersonGoncalves\ServiceDesk\Models\BusinessHoursTimeSlot;
use JeffersonGoncalves\ServiceDesk\Models\Holiday;
use JeffersonGoncalves\ServiceDesk\Services\BusinessHoursService;

beforeEach(function () {
    $this->service = app(BusinessHoursService::class);
});

function makeWeekdaySchedule(bool $isDefault = true): BusinessHoursSchedule
{
    $schedule = BusinessHoursSchedule::create([
        'name' => 'Standard Business Hours',
        'timezone' => 'UTC',
        'is_active' => true,
        'is_default' => $isDefault,
    ]);

    foreach ([DayOfWeek::Monday, DayOfWeek::Tuesday, DayOfWeek::Wednesday, DayOfWeek::Thursday, DayOfWeek::Friday] as $day) {
        BusinessHoursTimeSlot::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => $day,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    return $schedule;
}

// ── calculateDueDate() ───────────────────────────────────────────────────────

it('returns a plain minute offset when no schedule is available', function () {
    $start = Carbon::parse('2024-01-01 10:00:00', 'UTC'); // Monday

    $due = $this->service->calculateDueDate($start, 60);

    expect($due->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

it('uses the configured default schedule id when no schedule is passed', function () {
    $schedule = makeWeekdaySchedule();
    config()->set('service-desk.sla.default_business_hours_schedule', $schedule->id);

    $start = Carbon::parse('2024-01-01 10:00:00', 'UTC'); // Monday

    $due = $this->service->calculateDueDate($start, 60);

    expect($due->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

it('falls back to the active default schedule when none is configured', function () {
    makeWeekdaySchedule(isDefault: true);

    $start = Carbon::parse('2024-01-01 10:00:00', 'UTC'); // Monday

    $due = $this->service->calculateDueDate($start, 60);

    expect($due->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

// ── addBusinessMinutes() ─────────────────────────────────────────────────────

it('adds minutes within the same business hours slot', function () {
    $schedule = makeWeekdaySchedule();
    $start = Carbon::parse('2024-01-01 10:00:00', 'UTC'); // Monday

    $due = $this->service->addBusinessMinutes($start, 60, $schedule);

    expect($due->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

it('rolls over to the next business day when minutes exceed the slot', function () {
    $schedule = makeWeekdaySchedule();
    $start = Carbon::parse('2024-01-05 16:30:00', 'UTC'); // Friday, 30 min left in slot

    $due = $this->service->addBusinessMinutes($start, 90, $schedule);

    // 30 min consumed Friday, remaining 60 min starting Monday 09:00.
    expect($due->toDateTimeString())->toBe('2024-01-08 10:00:00');
});

it('skips a holiday when calculating the due date', function () {
    $schedule = makeWeekdaySchedule();

    Holiday::create([
        'schedule_id' => $schedule->id,
        'name' => 'Company Holiday',
        'date' => '2024-01-02', // Tuesday
        'is_recurring' => false,
    ]);

    $start = Carbon::parse('2024-01-01 16:30:00', 'UTC'); // Monday, 30 min left in slot

    $due = $this->service->addBusinessMinutes($start, 60, $schedule);

    // 30 min consumed Monday, Tuesday is a holiday, remaining 30 min on Wednesday.
    expect($due->toDateTimeString())->toBe('2024-01-03 09:30:00');
});

it('returns a plain minute offset when the schedule has no time slots', function () {
    $schedule = BusinessHoursSchedule::create([
        'name' => 'Empty Schedule',
        'timezone' => 'UTC',
        'is_active' => true,
        'is_default' => false,
    ]);

    $start = Carbon::parse('2024-01-01 10:00:00', 'UTC');

    $due = $this->service->addBusinessMinutes($start, 60, $schedule);

    expect($due->toDateTimeString())->toBe('2024-01-01 11:00:00');
});

// ── isBusinessHour() ─────────────────────────────────────────────────────────

it('recognizes a datetime within business hours', function () {
    $schedule = makeWeekdaySchedule();
    $withinHours = Carbon::parse('2024-01-01 10:00:00', 'UTC'); // Monday

    expect($this->service->isBusinessHour($withinHours, $schedule))->toBeTrue();
});

it('recognizes a datetime outside business hours', function () {
    $schedule = makeWeekdaySchedule();
    $outsideHours = Carbon::parse('2024-01-01 20:00:00', 'UTC'); // Monday evening

    expect($this->service->isBusinessHour($outsideHours, $schedule))->toBeFalse();
});

it('treats a weekend datetime as outside business hours', function () {
    $schedule = makeWeekdaySchedule();
    $saturday = Carbon::parse('2024-01-06 10:00:00', 'UTC');

    expect($this->service->isBusinessHour($saturday, $schedule))->toBeFalse();
});

it('treats a holiday datetime as outside business hours even within slot times', function () {
    $schedule = makeWeekdaySchedule();

    Holiday::create([
        'schedule_id' => $schedule->id,
        'name' => 'Holiday',
        'date' => '2024-01-01',
        'is_recurring' => false,
    ]);

    $holidayDuringSlot = Carbon::parse('2024-01-01 10:00:00', 'UTC');

    expect($this->service->isBusinessHour($holidayDuringSlot, $schedule))->toBeFalse();
});

// ── isHoliday() ──────────────────────────────────────────────────────────────

it('matches a non-recurring holiday by exact date', function () {
    $schedule = makeWeekdaySchedule();

    Holiday::create([
        'schedule_id' => $schedule->id,
        'name' => 'One-off Holiday',
        'date' => '2024-03-15',
        'is_recurring' => false,
    ]);

    expect($this->service->isHoliday(Carbon::parse('2024-03-15', 'UTC'), $schedule))->toBeTrue()
        ->and($this->service->isHoliday(Carbon::parse('2025-03-15', 'UTC'), $schedule))->toBeFalse();
});

it('matches a recurring holiday by month and day regardless of year', function () {
    $schedule = makeWeekdaySchedule();

    Holiday::create([
        'schedule_id' => $schedule->id,
        'name' => 'Christmas',
        'date' => '2024-12-25',
        'is_recurring' => true,
    ]);

    expect($this->service->isHoliday(Carbon::parse('2024-12-25', 'UTC'), $schedule))->toBeTrue()
        ->and($this->service->isHoliday(Carbon::parse('2030-12-25', 'UTC'), $schedule))->toBeTrue()
        ->and($this->service->isHoliday(Carbon::parse('2024-12-26', 'UTC'), $schedule))->toBeFalse();
});
