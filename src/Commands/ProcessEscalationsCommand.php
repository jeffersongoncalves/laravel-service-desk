<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Services\EscalationService;

class ProcessEscalationsCommand extends Command
{
    protected $signature = 'service-desk:process-escalations';

    protected $description = 'Process pending escalation rules for tickets with SLA';

    public function handle(EscalationService $escalationService): int
    {
        $this->info('Processing pending escalations...');
        $escalationService->processPendingEscalations();

        $this->info('Escalation processing completed.');

        return self::SUCCESS;
    }
}
