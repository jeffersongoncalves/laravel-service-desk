<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\SendGridDriver;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;

class SendGridWebhookController extends Controller
{
    public function __construct(
        protected InboundEmailService $inboundEmailService,
        protected EmailParser $emailParser,
        protected SendGridDriver $driver,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $parsed = $this->emailParser->parse($payload);

            $recipient = $parsed['to_addresses'][0] ?? ($payload['to'] ?? null);

            $channel = EmailChannel::query()
                ->active()
                ->byDriver($this->driver->getDriverName())
                ->where('email_address', $recipient)
                ->first();

            if (! $channel) {
                Log::warning('SendGrid webhook: no matching email channel found', [
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
            Log::error('SendGrid webhook processing failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
