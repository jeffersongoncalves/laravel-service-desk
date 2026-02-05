<?php

namespace JeffersonGoncalves\ServiceDesk\Mail\Drivers;

use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

class MailgunDriver implements EmailDriver
{
    protected EmailParser $parser;

    public function __construct(EmailParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Mailgun is webhook-based; polling is not applicable.
     */
    public function poll(EmailChannel $channel): array
    {
        return [];
    }

    /**
     * Get the driver name.
     */
    public function getDriverName(): string
    {
        return 'mailgun';
    }

    /**
     * Parse an inbound email from a Mailgun webhook payload.
     */
    public function parseWebhookPayload(array $payload): array
    {
        try {
            $rawData = [
                'message_id' => $payload['Message-Id'] ?? $payload['message-id'] ?? null,
                'in_reply_to' => $payload['In-Reply-To'] ?? $payload['in-reply-to'] ?? null,
                'references' => $payload['References'] ?? $payload['references'] ?? null,
                'from' => $payload['from'] ?? $payload['From'] ?? '',
                'to' => $payload['To'] ?? $payload['to'] ?? $payload['recipient'] ?? '',
                'cc' => $payload['Cc'] ?? $payload['cc'] ?? null,
                'subject' => $payload['subject'] ?? $payload['Subject'] ?? '',
                'text_body' => $payload['body-plain'] ?? $payload['stripped-text'] ?? null,
                'html_body' => $payload['body-html'] ?? $payload['stripped-html'] ?? null,
                'attachments' => $this->extractAttachments($payload),
            ];

            return $this->parser->parse($rawData);
        } catch (EmailProcessingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw EmailProcessingException::parsingFailed($e->getMessage());
        }
    }

    /**
     * Extract attachments from the Mailgun webhook payload.
     */
    protected function extractAttachments(array $payload): array
    {
        $attachments = [];

        if (isset($payload['attachments'])) {
            $attachmentData = is_string($payload['attachments'])
                ? json_decode($payload['attachments'], true) ?? []
                : $payload['attachments'];

            foreach ($attachmentData as $attachment) {
                $attachments[] = [
                    'filename' => $attachment['name'] ?? 'attachment',
                    'content_type' => $attachment['content-type'] ?? 'application/octet-stream',
                    'size' => $attachment['size'] ?? 0,
                    'url' => $attachment['url'] ?? null,
                ];
            }
        }

        return $attachments;
    }
}
