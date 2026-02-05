<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\ResendDriver;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;

class ResendWebhookController extends Controller
{
    public function __construct(
        protected InboundEmailService $inboundEmailService,
        protected EmailParser $emailParser,
        protected ResendDriver $driver,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        $type = $payload['type'] ?? null;

        if ($type !== 'email.received') {
            return response()->json(['status' => 'ok']);
        }

        try {
            $data = $payload['data'] ?? [];

            $emailContent = $this->fetchEmailContent($data);

            $merged = array_merge($data, $emailContent);
            $parsed = $this->emailParser->parse($merged);

            $recipient = $parsed['to_addresses'][0] ?? null;

            $channel = EmailChannel::query()
                ->active()
                ->byDriver($this->driver->getDriverName())
                ->where('email_address', $recipient)
                ->first();

            if (! $channel) {
                Log::warning('Resend webhook: no matching email channel found', [
                    'recipient' => $recipient,
                ]);

                return response()->json(['status' => 'ok']);
            }

            $this->inboundEmailService->store([
                'email_channel_id' => $channel->id,
                'message_id' => $parsed['message_id'],
                'in_reply_to' => $parsed['in_reply_to'],
                'references' => $parsed['references'],
                'from_address' => $parsed['from_address'],
                'from_name' => $parsed['from_name'],
                'to_addresses' => $parsed['to_addresses'],
                'cc_addresses' => $parsed['cc_addresses'],
                'subject' => $parsed['subject'],
                'text_body' => $parsed['text_body'],
                'html_body' => $parsed['html_body'],
                'raw_payload' => config('service-desk.email.inbound.store_raw_payload') ? $payload : null,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('Resend webhook processing failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function fetchEmailContent(array $data): array
    {
        $emailId = $data['email_id'] ?? null;
        $apiKey = config('service-desk.email.inbound.resend.api_key');

        if (! $emailId || ! $apiKey) {
            return [];
        }

        try {
            $response = Http::withToken($apiKey)
                ->get("https://api.resend.com/emails/{$emailId}");

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('Resend: failed to fetch email content via API', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }
}
