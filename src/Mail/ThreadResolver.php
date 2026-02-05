<?php

namespace JeffersonGoncalves\ServiceDesk\Mail;

use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class ThreadResolver
{
    /**
     * Attempt to resolve an inbound email to an existing ticket.
     *
     * Tries the following strategies in order:
     * 1. In-Reply-To header matching
     * 2. References header matching
     * 3. Subject line reference number matching
     */
    public function resolve(array $parsedEmail): ?Ticket
    {
        return $this->resolveByInReplyTo($parsedEmail)
            ?? $this->resolveByReferences($parsedEmail)
            ?? $this->resolveBySubject($parsedEmail);
    }

    /**
     * Resolve by the In-Reply-To header, matching against ticket email_message_id.
     */
    protected function resolveByInReplyTo(array $parsedEmail): ?Ticket
    {
        $inReplyTo = $parsedEmail['in_reply_to'] ?? null;

        if (empty($inReplyTo)) {
            return null;
        }

        $messageId = trim($inReplyTo, '<>');

        $ticket = Ticket::where('email_message_id', $messageId)->first();

        if ($ticket) {
            Log::debug('ServiceDesk: Resolved ticket by In-Reply-To header.', [
                'ticket_id' => $ticket->id,
                'message_id' => $messageId,
            ]);
        }

        return $ticket;
    }

    /**
     * Resolve by the References header, matching against any referenced message ID.
     */
    protected function resolveByReferences(array $parsedEmail): ?Ticket
    {
        $references = $parsedEmail['references'] ?? null;

        if (empty($references)) {
            return null;
        }

        // References can be a space-separated string of message IDs
        $messageIds = is_array($references)
            ? $references
            : preg_split('/\s+/', trim($references));

        $messageIds = array_map(fn (string $id) => trim($id, '<>'), $messageIds);
        $messageIds = array_filter($messageIds);

        if (empty($messageIds)) {
            return null;
        }

        $ticket = Ticket::whereIn('email_message_id', $messageIds)->first();

        if ($ticket) {
            Log::debug('ServiceDesk: Resolved ticket by References header.', [
                'ticket_id' => $ticket->id,
                'references' => $messageIds,
            ]);
        }

        return $ticket;
    }

    /**
     * Resolve by extracting a reference number from the email subject line.
     *
     * Looks for patterns like [SD-00001] or SD-00001 in the subject.
     */
    protected function resolveBySubject(array $parsedEmail): ?Ticket
    {
        $subject = $parsedEmail['subject'] ?? null;

        if (empty($subject)) {
            return null;
        }

        $prefix = config('service-desk.ticket.reference_prefix', 'SD');

        // Match patterns like [SD-00001], SD-00001, [SD-12345], etc.
        $pattern = '/\[?' . preg_quote($prefix, '/') . '-(\d+)\]?/i';

        if (! preg_match($pattern, $subject, $matches)) {
            return null;
        }

        $referenceNumber = $prefix . '-' . $matches[1];

        $ticket = Ticket::where('reference_number', $referenceNumber)->first();

        if ($ticket) {
            Log::debug('ServiceDesk: Resolved ticket by subject line reference.', [
                'ticket_id' => $ticket->id,
                'reference_number' => $referenceNumber,
            ]);
        }

        return $ticket;
    }
}
