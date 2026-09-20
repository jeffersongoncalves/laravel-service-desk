<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\AutomationService;

class RunAutomationsCommand extends Command
{
    protected $signature = 'service-desk:run-automations
        {--dry-run : Report which rules would match without applying anything}';

    protected $description = 'Sweep open tickets against every active automation rule, regardless of trigger';

    public function handle(AutomationService $automationService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $tickets = Ticket::query()->open()->get();

        if ($tickets->isEmpty()) {
            $this->info('No open tickets found.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Evaluating automation rules against {$tickets->count()} open ticket(s)...");

        $matchedCount = 0;

        foreach ($tickets as $ticket) {
            $matchedRuleIds = $automationService->evaluateAll($ticket, $dryRun);

            if ($matchedRuleIds === []) {
                continue;
            }

            $matchedCount++;
            $ruleList = implode(', ', $matchedRuleIds);
            $this->line("  #{$ticket->reference_number}: matched rule(s) {$ruleList}");
        }

        $this->info("Done. {$matchedCount} ticket(s) matched at least one rule.");

        return self::SUCCESS;
    }
}
