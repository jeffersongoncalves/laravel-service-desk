<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailProcessed;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailReceived;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;
use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\CommentService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

class ProcessInboundEmail implements ShouldQueue
{
    public function __construct(
        protected TicketService $ticketService,
        protected CommentService $commentService,
    ) {}

    public function handle(InboundEmailReceived $event): void
    {
        $inboundEmail = $event->inboundEmail;

        try {
            $user = $this->resolveUser($inboundEmail);

            if (! $user) {
                $inboundEmail->markIgnored();
                Log::info('ServiceDesk: Inbound email ignored - no matching user found', [
                    'from' => $inboundEmail->from_address,
                    'message_id' => $inboundEmail->message_id,
                ]);

                return;
            }

            // Try to find an existing ticket by threading (in_reply_to or references)
            $ticket = $this->resolveThread($inboundEmail);

            if ($ticket) {
                // Add comment to existing ticket
                $comment = $this->commentService->addReply(
                    $ticket,
                    $user,
                    $inboundEmail->text_body ?? $inboundEmail->html_body ?? '',
                    [
                        'email_message_id' => $inboundEmail->message_id,
                    ]
                );

                $inboundEmail->markProcessed($ticket->id, $comment->id);
            } else {
                // Create new ticket
                $department = $this->resolveDepartment($inboundEmail);

                $ticket = $this->ticketService->create([
                    'title' => $inboundEmail->subject ?? __('service-desk::tickets.no_subject'),
                    'description' => $inboundEmail->text_body ?? $inboundEmail->html_body ?? '',
                    'department_id' => $department?->id,
                    'source' => 'email',
                    'email_message_id' => $inboundEmail->message_id,
                ], $user);

                $inboundEmail->markProcessed($ticket->id);
            }

            event(new InboundEmailProcessed($inboundEmail));
        } catch (\Throwable $e) {
            $inboundEmail->markFailed($e->getMessage());

            Log::error('ServiceDesk: Failed to process inbound email', [
                'inbound_email_id' => $inboundEmail->id,
                'message_id' => $inboundEmail->message_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveUser(InboundEmail $inboundEmail): ?object
    {
        $userModel = config('service-desk.models.user');

        if (! $userModel || ! class_exists($userModel)) {
            return null;
        }

        return $userModel::where('email', $inboundEmail->from_address)->first();
    }

    protected function resolveThread(InboundEmail $inboundEmail): ?Ticket
    {
        // Try to match by in_reply_to header
        if ($inboundEmail->in_reply_to) {
            $ticket = Ticket::where('email_message_id', $inboundEmail->in_reply_to)->first();

            if ($ticket) {
                return $ticket;
            }
        }

        // Try to match by references header
        if ($inboundEmail->references) {
            $references = is_array($inboundEmail->references)
                ? $inboundEmail->references
                : explode(' ', $inboundEmail->references);

            foreach ($references as $reference) {
                $reference = trim($reference);

                if (empty($reference)) {
                    continue;
                }

                $ticket = Ticket::where('email_message_id', $reference)->first();

                if ($ticket) {
                    return $ticket;
                }
            }
        }

        // Try to extract reference number from subject line
        if ($inboundEmail->subject) {
            $prefix = preg_quote(config('service-desk.ticket.reference_prefix', 'SD'), '/');

            if (preg_match("/{$prefix}-(\d+)/", $inboundEmail->subject, $matches)) {
                $referenceNumber = $matches[0];
                $ticket = Ticket::where('reference_number', $referenceNumber)->first();

                if ($ticket) {
                    return $ticket;
                }
            }
        }

        return null;
    }

    protected function resolveDepartment(InboundEmail $inboundEmail): ?Department
    {
        // Check if the inbound email came through a specific email channel
        if ($inboundEmail->email_channel_id) {
            $channel = EmailChannel::find($inboundEmail->email_channel_id);

            if ($channel && $channel->department_id) {
                return $channel->department;
            }
        }

        // Try to match by recipient email address
        if ($inboundEmail->to_addresses) {
            $toAddresses = is_array($inboundEmail->to_addresses)
                ? $inboundEmail->to_addresses
                : [$inboundEmail->to_addresses];

            foreach ($toAddresses as $address) {
                $emailAddress = is_array($address) ? ($address['address'] ?? $address[0] ?? null) : $address;

                if (! $emailAddress) {
                    continue;
                }

                $channel = EmailChannel::where('email_address', $emailAddress)
                    ->where('is_active', true)
                    ->first();

                if ($channel && $channel->department_id) {
                    return $channel->department;
                }

                $department = Department::where('email', $emailAddress)
                    ->where('is_active', true)
                    ->first();

                if ($department) {
                    return $department;
                }
            }
        }

        // Fall back to first active department
        return Department::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
    }
}
