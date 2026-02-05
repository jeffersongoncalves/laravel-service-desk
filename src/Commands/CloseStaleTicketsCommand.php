<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

class CloseStaleTicketsCommand extends Command
{
    protected $signature = 'service-desk:close-stale
        {--days= : Number of days of inactivity before closing}
        {--status=resolved : Only close tickets with this status}
        {--dry-run : Show what would be closed without actually closing}';

    protected $description = 'Close tickets that have been stale for a given number of days';

    public function handle(TicketService $ticketService): int
    {
        $days = $this->option('days')
            ? (int) $this->option('days')
            : (int) config('service-desk.ticket.auto_close_days', 7);

        $statusValue = $this->option('status');
        $dryRun = $this->option('dry-run');

        $status = TicketStatus::tryFrom($statusValue);

        if (! $status) {
            $this->error("Invalid status: {$statusValue}");

            return self::FAILURE;
        }

        $tickets = Ticket::query()
            ->where('status', $status)
            ->where('updated_at', '<', now()->subDays($days))
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('No stale tickets found.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$tickets->count()} stale ticket(s) with status \"{$statusValue}\" older than {$days} day(s).");

        foreach ($tickets as $ticket) {
            if ($dryRun) {
                $this->line("  Would close: #{$ticket->reference_number} - {$ticket->subject}");
            } else {
                $ticketService->close($ticket);
                $this->line("  Closed: #{$ticket->reference_number} - {$ticket->subject}");
            }
        }

        return self::SUCCESS;
    }
}
