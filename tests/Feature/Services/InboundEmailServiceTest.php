<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailReceived;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InboundEmailService::class);
});

function makeEmailData(array $overrides = []): array
{
    return array_merge([
        'message_id' => '<'.uniqid('test-', true).'@example.com>',
        'from_address' => 'sender@example.com',
        'from_name' => 'Test Sender',
        'to_addresses' => ['support@example.com'],
        'subject' => 'Test Subject',
        'text_body' => 'Hello, I need help.',
        'status' => 'pending',
    ], $overrides);
}

// ── store() ─────────────────────────────────────────────────────────────────

it('stores a new inbound email', function () {
    Event::fake([InboundEmailReceived::class]);

    $data = makeEmailData(['message_id' => '<unique-123@example.com>']);

    $email = $this->service->store($data);

    expect($email)->toBeInstanceOf(InboundEmail::class)
        ->and($email->message_id)->toBe('<unique-123@example.com>')
        ->and($email->from_address)->toBe('sender@example.com')
        ->and($email->from_name)->toBe('Test Sender')
        ->and($email->subject)->toBe('Test Subject')
        ->and($email->text_body)->toBe('Hello, I need help.')
        ->and($email->status)->toBe('pending');

    Event::assertDispatched(InboundEmailReceived::class);
});

it('returns existing email on duplicate message_id (deduplication)', function () {
    Event::fake([InboundEmailReceived::class]);

    $data = makeEmailData(['message_id' => '<duplicate@example.com>']);

    $first = $this->service->store($data);
    $second = $this->service->store($data);

    expect($first->id)->toBe($second->id);

    // Event should only fire once (for the first creation)
    Event::assertDispatchedTimes(InboundEmailReceived::class, 1);
});

it('stores emails with different message_ids as separate records', function () {
    Event::fake([InboundEmailReceived::class]);

    $email1 = $this->service->store(makeEmailData(['message_id' => '<msg-1@example.com>']));
    $email2 = $this->service->store(makeEmailData(['message_id' => '<msg-2@example.com>']));

    expect($email1->id)->not->toBe($email2->id);
    expect(InboundEmail::count())->toBe(2);
});

// ── markProcessed() ─────────────────────────────────────────────────────────

it('marks an email as processed', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    $department = Department::create(['name' => 'IT', 'slug' => 'it']);
    $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

    $ticket = Ticket::create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'title' => 'From email',
        'description' => 'Created from email',
    ]);

    $this->service->markProcessed($email, $ticket->id);

    $email->refresh();

    expect($email->status)->toBe('processed')
        ->and($email->ticket_id)->toBe($ticket->id)
        ->and($email->processed_at)->not->toBeNull();
});

it('marks an email as processed with ticket and comment ids', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    $this->service->markProcessed($email, 1, 5);

    $email->refresh();

    expect($email->status)->toBe('processed')
        ->and($email->ticket_id)->toBe(1)
        ->and($email->comment_id)->toBe(5);
});

it('marks an email as processed without ticket id', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    $this->service->markProcessed($email);

    $email->refresh();

    expect($email->status)->toBe('processed')
        ->and($email->ticket_id)->toBeNull()
        ->and($email->processed_at)->not->toBeNull();
});

// ── markFailed() ────────────────────────────────────────────────────────────

it('marks an email as failed with error message', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    $this->service->markFailed($email, 'Could not parse email body');

    $email->refresh();

    expect($email->status)->toBe('failed')
        ->and($email->error_message)->toBe('Could not parse email body');
});

// ── markIgnored() ───────────────────────────────────────────────────────────

it('marks an email as ignored', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    $this->service->markIgnored($email);

    $email->refresh();

    expect($email->status)->toBe('ignored')
        ->and($email->processed_at)->not->toBeNull();
});

// ── cleanOldEmails() ────────────────────────────────────────────────────────

it('cleans old processed emails', function () {
    // Old processed email - should be deleted
    $old = InboundEmail::create(makeEmailData([
        'message_id' => '<old-processed@example.com>',
        'status' => 'processed',
    ]));
    // Manually set created_at to 60 days ago
    InboundEmail::where('id', $old->id)->update(['created_at' => now()->subDays(60)]);

    // Old ignored email - should be deleted
    $oldIgnored = InboundEmail::create(makeEmailData([
        'message_id' => '<old-ignored@example.com>',
        'status' => 'ignored',
    ]));
    InboundEmail::where('id', $oldIgnored->id)->update(['created_at' => now()->subDays(60)]);

    // Old failed email - should NOT be deleted
    $oldFailed = InboundEmail::create(makeEmailData([
        'message_id' => '<old-failed@example.com>',
        'status' => 'failed',
    ]));
    InboundEmail::where('id', $oldFailed->id)->update(['created_at' => now()->subDays(60)]);

    // Old pending email - should NOT be deleted
    $oldPending = InboundEmail::create(makeEmailData([
        'message_id' => '<old-pending@example.com>',
        'status' => 'pending',
    ]));
    InboundEmail::where('id', $oldPending->id)->update(['created_at' => now()->subDays(60)]);

    // Recent processed email - should NOT be deleted
    $recent = InboundEmail::create(makeEmailData([
        'message_id' => '<recent@example.com>',
        'status' => 'processed',
    ]));

    $deleted = $this->service->cleanOldEmails(30);

    expect($deleted)->toBe(2)
        ->and(InboundEmail::count())->toBe(3);
});

it('uses custom days parameter for cleanup', function () {
    $email = InboundEmail::create(makeEmailData([
        'message_id' => '<custom-days@example.com>',
        'status' => 'processed',
    ]));
    InboundEmail::where('id', $email->id)->update(['created_at' => now()->subDays(10)]);

    $deleted = $this->service->cleanOldEmails(5);

    expect($deleted)->toBe(1);
});

it('returns zero when no emails to clean', function () {
    $deleted = $this->service->cleanOldEmails(30);

    expect($deleted)->toBe(0);
});

// ── Model scopes ────────────────────────────────────────────────────────────

it('scopes pending emails', function () {
    InboundEmail::create(makeEmailData([
        'message_id' => '<pending@example.com>',
        'status' => 'pending',
    ]));

    InboundEmail::create(makeEmailData([
        'message_id' => '<processed@example.com>',
        'status' => 'processed',
    ]));

    expect(InboundEmail::pending()->count())->toBe(1);
});

it('scopes processed emails', function () {
    InboundEmail::create(makeEmailData([
        'message_id' => '<pending2@example.com>',
        'status' => 'pending',
    ]));

    InboundEmail::create(makeEmailData([
        'message_id' => '<processed2@example.com>',
        'status' => 'processed',
    ]));

    expect(InboundEmail::processed()->count())->toBe(1);
});

it('scopes failed emails', function () {
    InboundEmail::create(makeEmailData([
        'message_id' => '<ok@example.com>',
        'status' => 'pending',
    ]));

    InboundEmail::create(makeEmailData([
        'message_id' => '<fail@example.com>',
        'status' => 'failed',
        'error_message' => 'Something went wrong',
    ]));

    expect(InboundEmail::failed()->count())->toBe(1);
});

// ── Model helpers ───────────────────────────────────────────────────────────

it('checks isPending correctly', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());

    expect($email->isPending())->toBeTrue()
        ->and($email->isProcessed())->toBeFalse();
});

it('checks isProcessed correctly', function () {
    Event::fake([InboundEmailReceived::class]);

    $email = $this->service->store(makeEmailData());
    $this->service->markProcessed($email);
    $email->refresh();

    expect($email->isProcessed())->toBeTrue()
        ->and($email->isPending())->toBeFalse();
});
