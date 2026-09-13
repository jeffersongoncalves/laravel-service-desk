<?php

use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;

function makeCleanableEmail(string $status, int $daysOld): InboundEmail
{
    $email = InboundEmail::create([
        'message_id' => '<'.uniqid('clean-', true).'@example.com>',
        'from_address' => 'sender@example.com',
        'to_addresses' => ['support@example.com'],
        'status' => $status,
    ]);

    InboundEmail::where('id', $email->id)->update(['created_at' => now()->subDays($daysOld)]);

    return $email->fresh();
}

it('cleans old processed and ignored emails using the configured retention', function () {
    config()->set('service-desk.email.inbound.retention_days', 30);

    makeCleanableEmail('processed', 60);
    makeCleanableEmail('ignored', 60);
    $keep = makeCleanableEmail('pending', 60);

    $this->artisan('service-desk:clean-emails')
        ->expectsOutputToContain('Cleaned 2 old inbound email(s).')
        ->assertExitCode(0);

    expect(InboundEmail::count())->toBe(1)
        ->and(InboundEmail::find($keep->id))->not->toBeNull();
});

it('accepts an explicit --days option', function () {
    makeCleanableEmail('processed', 10);

    $this->artisan('service-desk:clean-emails', ['--days' => 5])
        ->expectsOutputToContain('Cleaned 1 old inbound email(s).')
        ->assertExitCode(0);

    expect(InboundEmail::count())->toBe(0);
});

it('reports zero when nothing needs cleaning', function () {
    $this->artisan('service-desk:clean-emails')
        ->expectsOutputToContain('Cleaned 0 old inbound email(s).')
        ->assertExitCode(0);
});
