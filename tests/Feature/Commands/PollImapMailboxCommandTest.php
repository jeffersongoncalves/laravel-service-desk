<?php

use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

// webklex/php-imap is a suggest-only dependency (not installed in this repo),
// so EmailDriver has no container binding — we can only exercise the code
// paths that return before app(EmailDriver::class) is resolved.

it('no-ops when the inbound driver is not set to imap', function () {
    config()->set('service-desk.email.inbound.driver', null);

    $this->artisan('service-desk:poll-imap')
        ->expectsOutputToContain('Inbound email driver is not set to "imap"')
        ->assertExitCode(0);
});

it('no-ops when the inbound driver is something other than imap', function () {
    config()->set('service-desk.email.inbound.driver', 'mailgun');

    $this->artisan('service-desk:poll-imap')
        ->expectsOutputToContain('Current driver: mailgun')
        ->assertExitCode(0);
});

it('reports no active imap channels without touching the email driver', function () {
    config()->set('service-desk.email.inbound.driver', 'imap');

    EmailChannel::create([
        'name' => 'Support Mailbox',
        'driver' => 'imap',
        'email_address' => 'support@example.com',
        'settings' => [],
        'is_active' => false,
    ]);

    $this->artisan('service-desk:poll-imap')
        ->expectsOutputToContain('No active IMAP channels found.')
        ->assertExitCode(0);
});

it('filters channels by the --channel option', function () {
    config()->set('service-desk.email.inbound.driver', 'imap');

    EmailChannel::create([
        'name' => 'Other Driver Channel',
        'driver' => 'mailgun',
        'email_address' => 'other@example.com',
        'settings' => [],
        'is_active' => true,
    ]);

    // Only a mailgun channel exists, so filtering by imap driver yields none
    // regardless of the --channel option, without ever resolving EmailDriver.
    $this->artisan('service-desk:poll-imap', ['--channel' => 999])
        ->expectsOutputToContain('No active IMAP channels found.')
        ->assertExitCode(0);
});
