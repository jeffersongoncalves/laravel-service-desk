<?php

namespace JeffersonGoncalves\ServiceDesk\Mail;

use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;

class EmailParser
{
    /**
     * Parse raw email data into a standardized format.
     */
    public function parse(array $data): array
    {
        try {
            return [
                'message_id' => $this->extractMessageId($data),
                'in_reply_to' => $data['in_reply_to'] ?? $data['In-Reply-To'] ?? null,
                'references' => $data['references'] ?? $data['References'] ?? null,
                'from_address' => $this->extractFromAddress($data),
                'from_name' => $this->extractFromName($data),
                'to_addresses' => $this->extractAddresses($data, 'to'),
                'cc_addresses' => $this->extractAddresses($data, 'cc'),
                'subject' => $data['subject'] ?? $data['Subject'] ?? '',
                'text_body' => $this->extractTextBody($data),
                'html_body' => $data['html_body'] ?? $data['html'] ?? $data['body-html'] ?? null,
                'raw_payload' => $data,
                'attachments' => $data['attachments'] ?? [],
            ];
        } catch (\Throwable $e) {
            throw EmailProcessingException::parsingFailed($e->getMessage());
        }
    }

    /**
     * Extract or generate a message ID from the email data.
     */
    public function extractMessageId(array $data): string
    {
        $messageId = $data['message_id']
            ?? $data['Message-ID']
            ?? $data['Message-Id']
            ?? $data['message-id']
            ?? null;

        if ($messageId) {
            return trim($messageId, '<>');
        }

        return sprintf(
            'servicedesk-%s@%s',
            uniqid('servicedesk-', true),
            gethostname() ?: 'localhost'
        );
    }

    /**
     * Extract the sender's email address from the data.
     */
    public function extractFromAddress(array $data): string
    {
        if (isset($data['from_address'])) {
            return $data['from_address'];
        }

        if (isset($data['from'])) {
            return $this->parseEmailFromString($data['from']);
        }

        if (isset($data['From'])) {
            return $this->parseEmailFromString($data['From']);
        }

        if (isset($data['sender'])) {
            return $this->parseEmailFromString($data['sender']);
        }

        throw EmailProcessingException::parsingFailed('Unable to extract sender email address.');
    }

    /**
     * Extract the sender's display name from the data.
     */
    public function extractFromName(array $data): ?string
    {
        if (isset($data['from_name'])) {
            return $data['from_name'];
        }

        if (isset($data['from'])) {
            return $this->parseNameFromString($data['from']);
        }

        if (isset($data['From'])) {
            return $this->parseNameFromString($data['From']);
        }

        return null;
    }

    /**
     * Extract addresses (to/cc) from the data into an array.
     */
    public function extractAddresses(array $data, string $type): array
    {
        $key = $type;
        $value = $data[$key] ?? $data[ucfirst($key)] ?? $data["{$key}_addresses"] ?? null;

        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return $item['email'] ?? $item['address'] ?? $item;
                }

                return $this->parseEmailFromString((string) $item);
            }, $value);
        }

        if (is_string($value)) {
            return array_map(
                fn (string $address) => $this->parseEmailFromString(trim($address)),
                explode(',', $value)
            );
        }

        return [];
    }

    /**
     * Extract the plain text body from the email data.
     */
    public function extractTextBody(array $data): ?string
    {
        $body = $data['text_body']
            ?? $data['text']
            ?? $data['body-plain']
            ?? $data['stripped-text']
            ?? $data['TextBody']
            ?? $data['StrippedTextReply']
            ?? null;

        if ($body === null) {
            return null;
        }

        return $this->cleanTextBody($body);
    }

    /**
     * Clean the text body by removing quoted replies and signatures.
     */
    public function cleanTextBody(string $body): string
    {
        // Remove common email reply markers
        $patterns = [
            '/^>.*$/m',                                      // Lines starting with >
            '/^On .+ wrote:$/m',                             // "On ... wrote:" lines
            '/^-{2,}\s*Original Message\s*-{2,}.*$/ms',     // -- Original Message --
            '/^_{2,}\s*$/m',                                 // Lines of underscores (Outlook separator)
            '/^From:.*$/ms',                                 // From: header in quoted replies
            '/^Sent:.*$/m',                                  // Sent: header in quoted replies
            '/^To:.*$/m',                                    // To: header in quoted replies
            '/^Subject:.*$/m',                               // Subject: header in quoted replies
        ];

        $cleaned = $body;

        // Split at common reply separators and take only the first part
        $separators = [
            "\n-- \n",                    // Standard email signature delimiter
            "\n--\n",                     // Variation without trailing space
            "\n___",                      // Outlook separator
            "\n---",                      // Common separator
            "\nOn ",                      // Gmail-style reply
        ];

        foreach ($separators as $separator) {
            $position = strpos($cleaned, $separator);
            if ($position !== false && $position > 0) {
                $cleaned = substr($cleaned, 0, $position);
                break;
            }
        }

        // Remove quoted lines (lines starting with >)
        $cleaned = preg_replace('/^>.*$/m', '', $cleaned);

        // Remove excessive blank lines
        $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);

        return trim($cleaned);
    }

    /**
     * Parse an email address from a string like "Name <email@example.com>" or "email@example.com".
     */
    public function parseEmailFromString(string $string): string
    {
        $string = trim($string);

        if (preg_match('/<([^>]+)>/', $string, $matches)) {
            return trim($matches[1]);
        }

        // If the string looks like a bare email address
        if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
            return $string;
        }

        // Try to extract anything that looks like an email
        if (preg_match('/[\w.+-]+@[\w.-]+\.\w+/', $string, $matches)) {
            return $matches[0];
        }

        return $string;
    }

    /**
     * Parse a display name from a string like "Name <email@example.com>".
     */
    public function parseNameFromString(string $string): ?string
    {
        $string = trim($string);

        if (preg_match('/^(.+?)\s*<[^>]+>/', $string, $matches)) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");

            return $name !== '' ? $name : null;
        }

        return null;
    }
}
