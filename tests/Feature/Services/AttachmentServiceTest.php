<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ServiceDesk\Events\AttachmentAdded;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidAttachmentException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;
use JeffersonGoncalves\ServiceDesk\Services\AttachmentService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->service = app(AttachmentService::class);
    $this->ticket = Ticket::factory()->create();
    $this->uploader = User::create(['name' => 'Uploader', 'email' => 'uploader@example.com']);
});

// ── store() validation ───────────────────────────────────────────────────────

it('stores an allowed attachment', function () {
    Event::fake([AttachmentAdded::class]);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $attachment = $this->service->store($this->ticket, $file, $this->uploader);

    expect($attachment)->toBeInstanceOf(TicketAttachment::class)
        ->and($attachment->file_name)->toBe('document.pdf')
        ->and($attachment->ticket_id)->toBe($this->ticket->id);

    Storage::disk('local')->assertExists($attachment->file_path);
    Event::assertDispatched(AttachmentAdded::class);
});

it('rejects a disallowed extension on store', function () {
    $file = UploadedFile::fake()->create('malware.exe', 10);

    $this->service->store($this->ticket, $file, $this->uploader);
})->throws(InvalidAttachmentException::class);

it('rejects a file exceeding the size limit on store', function () {
    // Default max_file_size is 10240 KB.
    $file = UploadedFile::fake()->create('huge.pdf', 11000);

    $this->service->store($this->ticket, $file, $this->uploader);
})->throws(InvalidAttachmentException::class);

// ── storeFromPath() validation ───────────────────────────────────────────────

it('rejects a disallowed extension on storeFromPath', function () {
    $source = UploadedFile::fake()->create('payload.exe', 10)->getPathname();

    $this->service->storeFromPath($this->ticket, $source, 'payload.exe', 'application/octet-stream', 10 * 1024, $this->uploader);
})->throws(InvalidAttachmentException::class);

it('rejects an oversized file on storeFromPath', function () {
    $source = UploadedFile::fake()->create('big.pdf', 50)->getPathname();

    $this->service->storeFromPath($this->ticket, $source, 'big.pdf', 'application/pdf', 11000 * 1024, $this->uploader);
})->throws(InvalidAttachmentException::class);

it('stores a valid file via storeFromPath', function () {
    Event::fake([AttachmentAdded::class]);

    $source = tempnam(sys_get_temp_dir(), 'sd-attachment');
    file_put_contents($source, 'fake pdf content');

    try {
        $attachment = $this->service->storeFromPath($this->ticket, $source, 'report.pdf', 'application/pdf', 50 * 1024, $this->uploader);

        expect($attachment)->toBeInstanceOf(TicketAttachment::class)
            ->and($attachment->file_name)->toBe('report.pdf');

        Storage::disk('local')->assertExists($attachment->file_path);
        Event::assertDispatched(AttachmentAdded::class);
    } finally {
        @unlink($source);
    }
});

// ── helper methods ───────────────────────────────────────────────────────────

it('validates allowed extensions case-insensitively', function () {
    expect($this->service->isAllowedExtension('PDF'))->toBeTrue()
        ->and($this->service->isAllowedExtension('exe'))->toBeFalse();
});

it('validates the size limit', function () {
    expect($this->service->isWithinSizeLimit(100))->toBeTrue()
        ->and($this->service->isWithinSizeLimit(999999))->toBeFalse();
});
