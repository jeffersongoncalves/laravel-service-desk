<?php

namespace JeffersonGoncalves\ServiceDesk\Mail\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\ServiceDesk\Contracts\EmailDriver;
use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

class ResendDriver implements EmailDriver
{
    protected EmailParser $parser;

    public function __construct(EmailParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Resend is webhook-based; polling is not applicable.
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
        return 'resend';
    }

    /**
     * Parse an inbound email from a Resend webhook payload.
     */
    public function parseWebhookPayload(array $payload): array
    {
        try {
            $data = $payload['data'] ?? $payload;

            $rawData = [
                'message_id' => $data['email_id'] ?? $data['message_id'] ?? null,
                'in_reply_to' => $data['in_reply_to'] ?? null,
                'references' => $data['references'] ?? null,
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? [],
                'cc' => $data['cc'] ?? [],
                'subject' => $data['subject'] ?? '',
                'text_body' => $data['text'] ?? null,
                'html_body' => $data['html'] ?? null,
                'attachments' => $data['attachments'] ?? [],
            ];

            // If text body is not available, attempt to fetch the full email content
            if (empty($rawData['text_body']) && ! empty($data['email_id'])) {
                $emailContent = $this->fetchEmailContent($data['email_id']);
                if ($emailContent) {
                    $rawData['text_body'] = $emailContent['text'] ?? $rawData['text_body'];
                    $rawData['html_body'] = $emailContent['html'] ?? $rawData['html_body'];
                    $rawData['attachments'] = $emailContent['attachments'] ?? $rawData['attachments'];
                }
            }

            return $this->parser->parse($rawData);
        } catch (EmailProcessingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw EmailProcessingException::parsingFailed($e->getMessage());
        }
    }

    /**
     * Fetch the full email content from the Resend API.
     */
    public function fetchEmailContent(string $emailId): ?array
    {
        $apiKey = config('service-desk.email.inbound.resend.api_key');

        if (empty($apiKey)) {
            Log::warning('ServiceDesk: Resend API key not configured, cannot fetch email content.');

            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->get("https://api.resend.com/emails/{$emailId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('ServiceDesk: Failed to fetch Resend email content.', [
                'email_id' => $emailId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('ServiceDesk: Error fetching Resend email content.', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch attachments for an email from the Resend API.
     */
    public function fetchAttachments(string $emailId): array
    {
        $emailContent = $this->fetchEmailContent($emailId);

        if (! $emailContent || empty($emailContent['attachments'])) {
            return [];
        }

        $attachments = [];

        foreach ($emailContent['attachments'] as $attachment) {
            $attachments[] = [
                'filename' => $attachment['filename'] ?? 'attachment',
                'content_type' => $attachment['content_type'] ?? 'application/octet-stream',
                'content' => $attachment['content'] ?? null,
            ];
        }

        return $attachments;
    }

    /**
     * Verify a Resend webhook signature using Svix HMAC-SHA256.
     *
     * @param  string  $payload  The raw request body.
     * @param  array  $headers  The webhook request headers (svix-id, svix-timestamp, svix-signature).
     * @param  string|null  $secret  The webhook secret. Defaults to the configured secret.
     */
    public function verifyWebhookSignature(string $payload, array $headers, ?string $secret = null): bool
    {
        $secret = $secret ?? config('service-desk.email.inbound.resend.webhook_secret');

        if (empty($secret)) {
            Log::warning('ServiceDesk: Resend webhook secret not configured, skipping verification.');

            return true;
        }

        $svixId = $headers['svix-id'] ?? $headers['Svix-Id'] ?? null;
        $svixTimestamp = $headers['svix-timestamp'] ?? $headers['Svix-Timestamp'] ?? null;
        $svixSignature = $headers['svix-signature'] ?? $headers['Svix-Signature'] ?? null;

        if (! $svixId || ! $svixTimestamp || ! $svixSignature) {
            return false;
        }

        // Validate timestamp is within tolerance (5 minutes)
        $tolerance = 300;
        $now = time();
        $timestamp = (int) $svixTimestamp;

        if (abs($now - $timestamp) > $tolerance) {
            return false;
        }

        // Decode the secret (Svix secrets are base64 encoded with a "whsec_" prefix)
        $secretBytes = $secret;
        if (str_starts_with($secret, 'whsec_')) {
            $secretBytes = base64_decode(substr($secret, 6));
        }

        // Build the signed content
        $signedContent = "{$svixId}.{$svixTimestamp}.{$payload}";

        // Compute the expected signature
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $signedContent, $secretBytes, true)
        );

        // Compare against each signature in the header (comma-separated, prefixed with "v1,")
        $signatures = explode(' ', $svixSignature);

        foreach ($signatures as $sig) {
            $parts = explode(',', $sig, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$version, $sigValue] = $parts;

            if ($version !== 'v1') {
                continue;
            }

            if (hash_equals($expectedSignature, $sigValue)) {
                return true;
            }
        }

        return false;
    }
}
