<?php

namespace JeffersonGoncalves\ServiceDesk\Mail\Drivers;

use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

class PostmarkDriver implements EmailDriver
{
    protected EmailParser $parser;

    public function __construct(EmailParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Postmark is webhook-based; polling is not applicable.
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
        return 'postmark';
    }

    /**
     * Parse an inbound email from a Postmark webhook payload.
     */
    public function parseWebhookPayload(array $payload): array
    {
        try {
            $headers = $this->extractHeaders($payload['Headers'] ?? []);

            $rawData = [
                'message_id' => $payload['MessageID'] ?? $headers['Message-ID'] ?? null,
                'in_reply_to' => $headers['In-Reply-To'] ?? null,
                'references' => $headers['References'] ?? null,
                'from_address' => $this->extractFromFullAddress($payload),
                'from_name' => $this->extractFromFullName($payload),
                'to_addresses' => $this->extractFullAddresses($payload, 'ToFull'),
                'cc_addresses' => $this->extractFullAddresses($payload, 'CcFull'),
                'subject' => $payload['Subject'] ?? '',
                'text_body' => $payload['StrippedTextReply'] ?? $payload['TextBody'] ?? null,
                'html_body' => $payload['HtmlBody'] ?? null,
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
     * Extract the sender email address from the FromFull field.
     */
    protected function extractFromFullAddress(array $payload): string
    {
        if (isset($payload['FromFull']['Email'])) {
            return $payload['FromFull']['Email'];
        }

        if (isset($payload['From'])) {
            return (new EmailParser)->parseEmailFromString($payload['From']);
        }

        return '';
    }

    /**
     * Extract the sender name from the FromFull field.
     */
    protected function extractFromFullName(array $payload): ?string
    {
        if (isset($payload['FromFull']['Name']) && $payload['FromFull']['Name'] !== '') {
            return $payload['FromFull']['Name'];
        }

        if (isset($payload['From'])) {
            return (new EmailParser)->parseNameFromString($payload['From']);
        }

        return null;
    }

    /**
     * Extract email addresses from Postmark's *Full fields (ToFull, CcFull, BccFull).
     */
    protected function extractFullAddresses(array $payload, string $field): array
    {
        $addresses = [];

        if (! isset($payload[$field]) || ! is_array($payload[$field])) {
            return $addresses;
        }

        foreach ($payload[$field] as $entry) {
            if (isset($entry['Email'])) {
                $addresses[] = $entry['Email'];
            }
        }

        return $addresses;
    }

    /**
     * Extract headers from Postmark's Headers array into a key-value map.
     *
     * Postmark sends headers as an array of {Name, Value} objects.
     */
    protected function extractHeaders(array $headers): array
    {
        $map = [];

        foreach ($headers as $header) {
            if (isset($header['Name'], $header['Value'])) {
                $map[$header['Name']] = $header['Value'];
            }
        }

        return $map;
    }

    /**
     * Extract attachments from the Postmark webhook payload.
     *
     * Postmark provides attachment content as base64-encoded strings.
     */
    protected function extractAttachments(array $payload): array
    {
        $attachments = [];

        if (! isset($payload['Attachments']) || ! is_array($payload['Attachments'])) {
            return $attachments;
        }

        foreach ($payload['Attachments'] as $attachment) {
            $attachments[] = [
                'filename' => $attachment['Name'] ?? 'attachment',
                'content_type' => $attachment['ContentType'] ?? 'application/octet-stream',
                'size' => $attachment['ContentLength'] ?? 0,
                'content' => isset($attachment['Content'])
                    ? base64_decode($attachment['Content'])
                    : null,
                'content_id' => $attachment['ContentID'] ?? null,
            ];
        }

        return $attachments;
    }
}
