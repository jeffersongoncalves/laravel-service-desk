<?php

use JeffersonGoncalves\ServiceDesk\Mail\Drivers\ImapDriver;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\MailgunDriver;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\PostmarkDriver;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\ResendDriver;
use JeffersonGoncalves\ServiceDesk\Mail\Drivers\SendGridDriver;
use JeffersonGoncalves\ServiceDesk\Mail\EmailParser;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

beforeEach(function () {
    $this->channel = EmailChannel::create([
        'name' => 'Support Mailbox',
        'driver' => 'imap',
        'email_address' => 'support@example.com',
        'settings' => ['host' => 'imap.example.com'],
        'is_active' => true,
    ]);
});

// webklex/php-imap is a suggest-only dependency, not installed in this repo,
// so a real successful IMAP connection can't be exercised here -- only the
// dependency-missing failure path.
it('returns false when the imap package is not installed', function () {
    $driver = new ImapDriver(app(EmailParser::class));

    expect($driver->testConnection($this->channel))->toBeFalse();
});

it('returns true for the webhook-based mailgun driver without connecting anywhere', function () {
    $driver = new MailgunDriver(app(EmailParser::class));

    expect($driver->testConnection($this->channel))->toBeTrue();
});

it('returns true for the webhook-based sendgrid driver without connecting anywhere', function () {
    $driver = new SendGridDriver(app(EmailParser::class));

    expect($driver->testConnection($this->channel))->toBeTrue();
});

it('returns true for the webhook-based resend driver without connecting anywhere', function () {
    $driver = new ResendDriver(app(EmailParser::class));

    expect($driver->testConnection($this->channel))->toBeTrue();
});

it('returns true for the webhook-based postmark driver without connecting anywhere', function () {
    $driver = new PostmarkDriver(app(EmailParser::class));

    expect($driver->testConnection($this->channel))->toBeTrue();
});
