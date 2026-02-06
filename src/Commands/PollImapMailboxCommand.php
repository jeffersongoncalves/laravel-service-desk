<?php

namespace JeffersonGoncalves\ServiceDesk\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;

class PollImapMailboxCommand extends Command
{
    protected $signature = 'service-desk:poll-imap {--channel=}';

    protected $description = 'Poll IMAP mailboxes for new inbound emails';

    public function handle(InboundEmailService $inboundEmailService): int
    {
        $driver = config('service-desk.email.inbound.driver');

        if ($driver !== 'imap') {
            $this->warn('Inbound email driver is not set to "imap". Current driver: '.($driver ?? 'none'));

            return self::SUCCESS;
        }

        $query = EmailChannel::query()
            ->active()
            ->byDriver('imap');

        if ($channelId = $this->option('channel')) {
            $query->where('id', $channelId);
        }

        $channels = $query->get();

        if ($channels->isEmpty()) {
            $this->info('No active IMAP channels found.');

            return self::SUCCESS;
        }

        $imapDriver = app(EmailDriver::class);

        foreach ($channels as $channel) {
            $this->info("Polling channel: {$channel->name} ({$channel->email_address})");

            try {
                $emails = $imapDriver->poll($channel);

                foreach ($emails as $emailData) {
                    $inboundEmailService->store(array_merge($emailData, [
                        'email_channel_id' => $channel->id,
                        'status' => 'pending',
                    ]));
                }

                $channel->markPolled();

                $this->info('  Fetched '.count($emails).' email(s).');
            } catch (\Throwable $e) {
                $channel->markError($e->getMessage());

                $this->error("  Error: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
