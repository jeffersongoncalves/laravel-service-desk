<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'service-desk:check-sla
        {--dry-run : Report what would be marked as breached without changing anything or sending notifications}';

    protected $description = 'Check for SLA breaches and near-breaches on open tickets';

    public function handle(SlaService $slaService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Checking for SLA breaches...');
        $breachedCount = $slaService->checkBreaches($dryRun);
        $this->line("  {$breachedCount} breach(es) found.");

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Checking for near-breaches...');
        $nearBreachCount = $slaService->checkNearBreaches($dryRun);
        $this->line("  {$nearBreachCount} near-breach(es) found.");

        $this->info('SLA breach check completed.');

        return self::SUCCESS;
    }
}
