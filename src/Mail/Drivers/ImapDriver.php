<?php

namespace JeffersonGoncalves\ServiceDesk\Mail\Drivers;

use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

class ImapDriver implements EmailDriver
{
    protected EmailParser $parser;

    public function __construct(EmailParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Poll the IMAP mailbox for new messages.
     */
    public function poll(EmailChannel $channel): array
    {
        $this->ensureDependenciesInstalled();

        $settings = $channel->settings ?? [];
        $globalConfig = config('service-desk.email.inbound.imap', []);

        $config = array_merge($globalConfig, $settings);

        try {
            /** @var \Webklex\PHPIMAP\ClientManager $clientManager */
            $clientManager = new \Webklex\PHPIMAP\ClientManager;

            $client = $clientManager->make([
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? 993,
                'encryption' => $config['encryption'] ?? 'ssl',
                'validate_cert' => $config['validate_cert'] ?? true,
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'protocol' => 'imap',
            ]);

            $client->connect();

            $folder = $client->getFolder($config['folder'] ?? 'INBOX');

            if (! $folder) {
                throw EmailProcessingException::connectionFailed(
                    sprintf('IMAP folder "%s" not found.', $config['folder'] ?? 'INBOX')
                );
            }

            $messages = $folder->query()->unseen()->get();

            $emails = [];

            foreach ($messages as $message) {
                $rawData = $this->extractMessageData($message);
                $parsed = $this->parser->parse($rawData);

                $emails[] = $parsed;

                // Mark as read if configured
                if ($config['mark_as_read'] ?? true) {
                    $message->setFlag('Seen');
                }

                // Move to processed folder if configured
                if (! empty($config['move_processed_to'])) {
                    $message->move($config['move_processed_to']);
                }
            }

            return $emails;
        } catch (EmailProcessingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw EmailProcessingException::connectionFailed($e->getMessage());
        }
    }

    /**
     * Get the driver name.
     */
    public function getDriverName(): string
    {
        return 'imap';
    }

    /**
     * Extract message data from a webklex IMAP message into a raw array.
     */
    protected function extractMessageData(object $message): array
    {
        $from = $message->getFrom();
        $to = $message->getTo();
        $cc = $message->getCc();

        $fromAddress = '';
        $fromName = null;

        if ($from && count($from) > 0) {
            $firstFrom = $from[0] ?? null;
            if ($firstFrom) {
                $fromAddress = $firstFrom->mail ?? '';
                $fromName = $firstFrom->personal ?? null;
            }
        }

        $toAddresses = [];
        if ($to) {
            foreach ($to as $address) {
                $toAddresses[] = $address->mail ?? (string) $address;
            }
        }

        $ccAddresses = [];
        if ($cc) {
            foreach ($cc as $address) {
                $ccAddresses[] = $address->mail ?? (string) $address;
            }
        }

        $attachments = [];
        if ($message->hasAttachments()) {
            foreach ($message->getAttachments() as $attachment) {
                $attachments[] = [
                    'filename' => $attachment->getName(),
                    'content_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                    'content' => $attachment->getContent(),
                ];
            }
        }

        return [
            'message_id' => $message->getMessageId()?->first(),
            'in_reply_to' => $message->getInReplyTo()?->first(),
            'references' => $message->getReferences()?->first(),
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'to_addresses' => $toAddresses,
            'cc_addresses' => $ccAddresses,
            'subject' => $message->getSubject()?->first() ?? '',
            'text_body' => $message->getTextBody(),
            'html_body' => $message->getHtmlBody(),
            'attachments' => $attachments,
        ];
    }

    /**
     * Ensure the webklex/php-imap package is installed.
     */
    protected function ensureDependenciesInstalled(): void
    {
        if (! class_exists(\Webklex\PHPIMAP\ClientManager::class)) {
            throw EmailProcessingException::driverNotInstalled('imap', 'webklex/php-imap');
        }
    }
}
