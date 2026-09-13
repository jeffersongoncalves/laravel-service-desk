<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create();
    $this->uploader = User::create(['name' => 'Uploader', 'email' => 'uploader@example.com']);
});

function makeAttachment(Ticket $ticket, User $uploader, array $overrides = []): TicketAttachment
{
    return TicketAttachment::create(array_merge([
        'ticket_id' => $ticket->id,
        'uploaded_by_type' => $uploader->getMorphClass(),
        'uploaded_by_id' => $uploader->id,
        'file_name' => 'document.pdf',
        'file_path' => 'attachments/document.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
    ], $overrides));
}

// ── booted() defaults ────────────────────────────────────────────────────────

it('auto generates a uuid on creation', function () {
    $attachment = makeAttachment($this->ticket, $this->uploader);

    expect($attachment->uuid)->not->toBeNull()
        ->and($attachment->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('defaults the disk to the configured attachment disk when omitted', function () {
    config()->set('service-desk.ticket.attachment_disk', 'local');

    $attachment = makeAttachment($this->ticket, $this->uploader);

    expect($attachment->disk)->toBe('local');
});

it('keeps an explicitly provided disk', function () {
    $attachment = makeAttachment($this->ticket, $this->uploader, ['disk' => 's3']);

    expect($attachment->disk)->toBe('s3');
});

// ── getFileSizeForHumans() ───────────────────────────────────────────────────

it('formats byte sizes for humans', function () {
    expect(makeAttachment($this->ticket, $this->uploader, ['file_size' => 500])->getFileSizeForHumans())->toBe('500 B')
        ->and(makeAttachment($this->ticket, $this->uploader, ['file_size' => 2048])->getFileSizeForHumans())->toBe('2 KB')
        ->and(makeAttachment($this->ticket, $this->uploader, ['file_size' => 5 * 1024 * 1024])->getFileSizeForHumans())->toBe('5 MB');
});

// ── getUrl() / getTemporaryUrl() ─────────────────────────────────────────────

it('builds a url from the configured disk', function () {
    Storage::fake('local');

    $attachment = makeAttachment($this->ticket, $this->uploader, ['disk' => 'local']);

    expect($attachment->getUrl())->toContain($attachment->file_path);
});

// ── route key ────────────────────────────────────────────────────────────────

it('uses uuid as route key name', function () {
    expect((new TicketAttachment)->getRouteKeyName())->toBe('uuid');
});
