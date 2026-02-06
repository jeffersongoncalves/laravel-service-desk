<?php

namespace JeffersonGoncalves\ServiceDesk\Mail\Drivers;

use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

class SendGridDriver implements EmailDriver
{
    protected EmailParser $parser;

    public function __construct(EmailParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * SendGrid is webhook-based; polling is not applicable.
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
        return 'sendgrid';
    }

    /**
     * Parse an inbound email from a SendGrid webhook payload.
     */
    public function parseWebhookPayload(array $payload): array
    {
        try {
            $headers = $this->extractHeaders($payload['headers'] ?? '');

            $rawData = [
                'message_id' => $headers['Message-ID'] ?? $headers['Message-Id'] ?? null,
                'in_reply_to' => $headers['In-Reply-To'] ?? null,
                'references' => $headers['References'] ?? null,
                'from' => $payload['from'] ?? $payload['From'] ?? '',
                'to' => $payload['to'] ?? $payload['To'] ?? '',
                'cc' => $payload['cc'] ?? $payload['Cc'] ?? null,
                'subject' => $payload['subject'] ?? $payload['Subject'] ?? '',
                'text_body' => $payload['text'] ?? $payload['email'] ?? null,
                'html_body' => $payload['html'] ?? null,
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
     * Extract headers from the SendGrid raw headers string.
     *
     * SendGrid sends headers as a single string with each header on its own line.
     */
    protected function extractHeaders(string $headersString): array
    {
        $headers = [];

        if (empty($headersString)) {
            return $headers;
        }

        $lines = explode("\n", $headersString);
        $currentKey = null;
        $currentValue = '';

        foreach ($lines as $line) {
            // Continuation line (starts with whitespace)
            if (preg_match('/^\s+/', $line) && $currentKey !== null) {
                $currentValue .= ' '.trim($line);

                continue;
            }

            // Save previous header
            if ($currentKey !== null) {
                $headers[$currentKey] = trim($currentValue);
            }

            // Parse new header line
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                $currentKey = trim($matches[1]);
                $currentValue = $matches[2];
            } else {
                $currentKey = null;
                $currentValue = '';
            }
        }

        // Save the last header
        if ($currentKey !== null) {
            $headers[$currentKey] = trim($currentValue);
        }

        return $headers;
    }

    /**
     * Extract attachments from the SendGrid webhook payload.
     */
    protected function extractAttachments(array $payload): array
    {
        $attachments = [];
        $attachmentCount = (int) ($payload['attachments'] ?? 0);

        for ($i = 1; $i <= $attachmentCount; $i++) {
            $key = "attachment{$i}";

            if (isset($payload[$key])) {
                $attachment = $payload[$key];

                $attachments[] = [
                    'filename' => $attachment['filename'] ?? $payload['attachment-info'][$key]['filename'] ?? "attachment{$i}",
                    'content_type' => $attachment['type'] ?? $payload['attachment-info'][$key]['type'] ?? 'application/octet-stream',
                    'size' => $attachment['size'] ?? 0,
                    'content' => $attachment['content'] ?? null,
                ];
            }
        }

        return $attachments;
    }
}
