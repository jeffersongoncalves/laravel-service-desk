<?php

use JeffersonGoncalves\ServiceDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;

beforeEach(function () {
    $this->parser = new EmailParser;
});

// --- parse() ---

it('parses a complete email payload', function () {
    $data = [
        'message_id' => '<abc123@example.com>',
        'in_reply_to' => '<parent@example.com>',
        'references' => '<ref1@example.com> <ref2@example.com>',
        'from_address' => 'john@example.com',
        'from_name' => 'John Doe',
        'to' => 'support@example.com',
        'cc' => 'manager@example.com',
        'subject' => 'Help needed',
        'text_body' => 'I need help with my order.',
        'html_body' => '<p>I need help with my order.</p>',
        'attachments' => [['name' => 'file.pdf']],
    ];

    $parsed = $this->parser->parse($data);

    expect($parsed)
        ->message_id->toBe('abc123@example.com')
        ->in_reply_to->toBe('<parent@example.com>')
        ->references->toBe('<ref1@example.com> <ref2@example.com>')
        ->from_address->toBe('john@example.com')
        ->from_name->toBe('John Doe')
        ->subject->toBe('Help needed')
        ->text_body->toBe('I need help with my order.')
        ->html_body->toBe('<p>I need help with my order.</p>')
        ->attachments->toHaveCount(1)
        ->raw_payload->toBe($data);
});

it('parses a minimal email payload', function () {
    $data = [
        'from_address' => 'user@example.com',
        'subject' => 'Test',
    ];

    $parsed = $this->parser->parse($data);

    expect($parsed)
        ->from_address->toBe('user@example.com')
        ->subject->toBe('Test')
        ->in_reply_to->toBeNull()
        ->references->toBeNull()
        ->html_body->toBeNull()
        ->attachments->toBeEmpty();
});

it('throws EmailProcessingException when from address is missing', function () {
    $this->parser->parse(['subject' => 'No sender']);
})->throws(EmailProcessingException::class);

// --- extractMessageId() ---

it('extracts message ID from message_id key', function () {
    expect($this->parser->extractMessageId(['message_id' => '<abc@example.com>']))
        ->toBe('abc@example.com');
});

it('extracts message ID from Message-ID key', function () {
    expect($this->parser->extractMessageId(['Message-ID' => '<xyz@example.com>']))
        ->toBe('xyz@example.com');
});

it('extracts message ID from Message-Id key (mixed case)', function () {
    expect($this->parser->extractMessageId(['Message-Id' => 'test@example.com']))
        ->toBe('test@example.com');
});

it('generates a message ID when none is provided', function () {
    $id = $this->parser->extractMessageId([]);

    expect($id)->toBeString()
        ->toContain('servicedesk-')
        ->toContain('@');
});

it('strips angle brackets from message ID', function () {
    expect($this->parser->extractMessageId(['message_id' => '<wrapped@example.com>']))
        ->toBe('wrapped@example.com');
});

// --- extractFromAddress() ---

it('extracts from_address directly', function () {
    expect($this->parser->extractFromAddress(['from_address' => 'direct@example.com']))
        ->toBe('direct@example.com');
});

it('extracts email from "from" string with name and angle brackets', function () {
    expect($this->parser->extractFromAddress(['from' => 'John Doe <john@example.com>']))
        ->toBe('john@example.com');
});

it('extracts email from "From" key (capitalized)', function () {
    expect($this->parser->extractFromAddress(['From' => 'Jane <jane@example.com>']))
        ->toBe('jane@example.com');
});

it('extracts email from "sender" key', function () {
    expect($this->parser->extractFromAddress(['sender' => 'sender@example.com']))
        ->toBe('sender@example.com');
});

it('throws when no from address can be found', function () {
    $this->parser->extractFromAddress([]);
})->throws(EmailProcessingException::class);

// --- extractFromName() ---

it('extracts from_name directly', function () {
    expect($this->parser->extractFromName(['from_name' => 'John Doe']))
        ->toBe('John Doe');
});

it('extracts name from "from" field with angle brackets', function () {
    expect($this->parser->extractFromName(['from' => 'Jane Smith <jane@example.com>']))
        ->toBe('Jane Smith');
});

it('extracts name from "From" key (capitalized)', function () {
    expect($this->parser->extractFromName(['From' => '"Bob Jones" <bob@example.com>']))
        ->toBe('Bob Jones');
});

it('returns null when no name is available', function () {
    expect($this->parser->extractFromName([]))->toBeNull();
});

it('returns null when from contains only an email', function () {
    expect($this->parser->extractFromName(['from' => 'noreply@example.com']))->toBeNull();
});

// --- extractAddresses() ---

it('extracts to addresses from a comma-separated string', function () {
    $addresses = $this->parser->extractAddresses(
        ['to' => 'alice@example.com, bob@example.com'],
        'to'
    );

    expect($addresses)->toBe(['alice@example.com', 'bob@example.com']);
});

it('extracts to addresses from an array of strings', function () {
    $addresses = $this->parser->extractAddresses(
        ['to' => ['alice@example.com', 'Bob <bob@example.com>']],
        'to'
    );

    expect($addresses)->toBe(['alice@example.com', 'bob@example.com']);
});

it('extracts to addresses from an array of associative arrays', function () {
    $addresses = $this->parser->extractAddresses(
        ['to' => [
            ['email' => 'alice@example.com'],
            ['address' => 'bob@example.com'],
        ]],
        'to'
    );

    expect($addresses)->toBe(['alice@example.com', 'bob@example.com']);
});

it('extracts cc addresses with capitalized key', function () {
    $addresses = $this->parser->extractAddresses(
        ['Cc' => 'manager@example.com'],
        'cc'
    );

    expect($addresses)->toBe(['manager@example.com']);
});

it('returns empty array when address type is not present', function () {
    expect($this->parser->extractAddresses([], 'to'))->toBeEmpty()
        ->and($this->parser->extractAddresses([], 'cc'))->toBeEmpty();
});

it('extracts addresses from to_addresses key', function () {
    $addresses = $this->parser->extractAddresses(
        ['to_addresses' => ['first@example.com']],
        'to'
    );

    expect($addresses)->toBe(['first@example.com']);
});

// --- extractTextBody() ---

it('extracts text body from text_body key', function () {
    expect($this->parser->extractTextBody(['text_body' => 'Hello world']))
        ->toBe('Hello world');
});

it('extracts text body from text key', function () {
    expect($this->parser->extractTextBody(['text' => 'Hello text']))
        ->toBe('Hello text');
});

it('extracts text body from body-plain key', function () {
    expect($this->parser->extractTextBody(['body-plain' => 'Plain body']))
        ->toBe('Plain body');
});

it('extracts text body from stripped-text key', function () {
    expect($this->parser->extractTextBody(['stripped-text' => 'Stripped text']))
        ->toBe('Stripped text');
});

it('extracts text body from TextBody key (Postmark style)', function () {
    expect($this->parser->extractTextBody(['TextBody' => 'Postmark body']))
        ->toBe('Postmark body');
});

it('returns null when no text body is present', function () {
    expect($this->parser->extractTextBody([]))->toBeNull();
});

// --- cleanTextBody() ---

it('removes quoted reply lines starting with >', function () {
    $body = "My reply\n> Original message\n> Another line";

    expect($this->parser->cleanTextBody($body))->toBe('My reply');
});

it('strips content after standard signature delimiter', function () {
    $body = "Main content\n-- \nJohn Doe\nCompany Inc.";

    expect($this->parser->cleanTextBody($body))->toBe('Main content');
});

it('strips content after Gmail-style reply separator', function () {
    $body = "My response\nOn Mon, Jan 1, 2024 at 10:00 AM someone wrote:";

    expect($this->parser->cleanTextBody($body))->toBe('My response');
});

it('strips content after Outlook separator', function () {
    $body = "Reply text\n________________________________\nFrom: Someone";

    expect($this->parser->cleanTextBody($body))->toBe('Reply text');
});

it('strips content after dash separator', function () {
    $body = "My message\n---\nOriginal stuff below";

    expect($this->parser->cleanTextBody($body))->toBe('My message');
});

it('collapses excessive blank lines', function () {
    $body = "Line one\n\n\n\n\nLine two";

    expect($this->parser->cleanTextBody($body))->toBe("Line one\n\nLine two");
});

it('trims whitespace from cleaned body', function () {
    expect($this->parser->cleanTextBody("  Hello  \n  "))->toBe('Hello');
});

// --- parseEmailFromString() ---

it('extracts email from angle bracket format', function () {
    expect($this->parser->parseEmailFromString('John Doe <john@example.com>'))
        ->toBe('john@example.com');
});

it('returns bare email address as-is', function () {
    expect($this->parser->parseEmailFromString('simple@example.com'))
        ->toBe('simple@example.com');
});

it('extracts email-like pattern from messy string', function () {
    expect($this->parser->parseEmailFromString('Contact: user@domain.org for info'))
        ->toBe('user@domain.org');
});

// --- parseNameFromString() ---

it('parses name from "Name <email>" format', function () {
    expect($this->parser->parseNameFromString('Alice Cooper <alice@example.com>'))
        ->toBe('Alice Cooper');
});

it('parses name from quoted format', function () {
    expect($this->parser->parseNameFromString('"Bob Smith" <bob@example.com>'))
        ->toBe('Bob Smith');
});

it('returns null for bare email address', function () {
    expect($this->parser->parseNameFromString('noreply@example.com'))->toBeNull();
});

it('returns null for empty string', function () {
    expect($this->parser->parseNameFromString(''))->toBeNull();
});
