<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

function makeStaleTicket(TicketStatus $status, int $daysOld): Ticket
{
    $ticket = Ticket::factory()->status($status)->create();
    Ticket::where('id', $ticket->id)->update(['updated_at' => now()->subDays($daysOld)]);

    return $ticket->fresh();
}

it('closes stale tickets past the configured number of days', function () {
    $stale = makeStaleTicket(TicketStatus::Resolved, 10);
    $fresh = makeStaleTicket(TicketStatus::Resolved, 1);

    $this->artisan('service-desk:close-stale', ['--days' => 7])
        ->assertExitCode(0);

    expect($stale->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($fresh->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('does not close anything in dry-run mode', function () {
    $stale = makeStaleTicket(TicketStatus::Resolved, 10);

    $this->artisan('service-desk:close-stale', ['--days' => 7, '--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN]')
        ->assertExitCode(0);

    expect($stale->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('only closes tickets matching the given status option', function () {
    $resolved = makeStaleTicket(TicketStatus::Resolved, 10);
    $inProgress = makeStaleTicket(TicketStatus::InProgress, 10);

    $this->artisan('service-desk:close-stale', ['--days' => 7, '--status' => 'resolved'])
        ->assertExitCode(0);

    expect($resolved->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($inProgress->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('fails with an invalid status option', function () {
    $this->artisan('service-desk:close-stale', ['--status' => 'not-a-status'])
        ->assertExitCode(1);
});

it('reports when no stale tickets are found', function () {
    $this->artisan('service-desk:close-stale', ['--days' => 7])
        ->expectsOutputToContain('No stale tickets found.')
        ->assertExitCode(0);
});
