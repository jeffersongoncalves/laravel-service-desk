<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

class RecalculateSlaCommand extends Command
{
    protected $signature = 'service-desk:recalculate-sla
        {--policy= : Specific SLA policy ID}';

    protected $description = 'Recalculate SLA due dates for all active tickets with SLA';

    public function handle(SlaService $slaService): int
    {
        $policyId = $this->option('policy');

        $query = Ticket::query()
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Resolved->value,
            ])
            ->whereHas('ticketSla');

        if ($policyId) {
            $policy = SlaPolicy::find($policyId);

            if (! $policy) {
                $this->error("SLA Policy with ID {$policyId} not found.");

                return self::FAILURE;
            }

            $query->whereHas('ticketSla', function ($q) use ($policyId) {
                $q->where('sla_policy_id', $policyId);
            });

            $this->info("Recalculating SLA for policy: {$policy->name}");
        } else {
            $this->info('Recalculating SLA for all active tickets...');
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets found to recalculate.');

            return self::SUCCESS;
        }

        $this->info("Found {$tickets->count()} ticket(s) to recalculate.");

        $bar = $this->output->createProgressBar($tickets->count());
        $bar->start();

        foreach ($tickets as $ticket) {
            $ticketSla = $ticket->ticketSla;

            if ($ticketSla) {
                $policy = $ticketSla->slaPolicy;

                if ($policy) {
                    $slaService->applyPolicy($ticket, $policy);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('SLA recalculation completed.');

        return self::SUCCESS;
    }
}
