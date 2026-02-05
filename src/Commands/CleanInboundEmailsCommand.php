<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;

class CleanInboundEmailsCommand extends Command
{
    protected $signature = 'service-desk:clean-emails {--days=}';

    protected $description = 'Clean old processed and ignored inbound emails';

    public function handle(InboundEmailService $inboundEmailService): int
    {
        $days = $this->option('days')
            ? (int) $this->option('days')
            : null;

        $deleted = $inboundEmailService->cleanOldEmails($days);

        $this->info("Cleaned {$deleted} old inbound email(s).");

        return self::SUCCESS;
    }
}
