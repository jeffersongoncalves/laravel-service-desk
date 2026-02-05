<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'service-desk:check-sla';

    protected $description = 'Check for SLA breaches and near-breaches on open tickets';

    public function handle(SlaService $slaService): int
    {
        $this->info('Checking for SLA breaches...');
        $slaService->checkBreaches();

        $this->info('Checking for near-breaches...');
        $slaService->checkNearBreaches();

        $this->info('SLA breach check completed.');

        return self::SUCCESS;
    }
}
