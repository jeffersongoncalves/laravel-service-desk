<?php

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    Storage::fake(config('service-desk.ticket.attachment_disk', 'local'));

    config()->set('service-desk.api.clients', [
        'satellite-1' => ['secrets' => ['secret-1'], 'actor_types' => [User::class]],
        'satellite-2' => ['secrets' => ['secret-2'], 'actor_types' => [User::class]],
    ]);

    $this->department = Department::factory()->create();
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $this->call = function (string $method, string $appKey, string $secret, string $uri, array $payload = []) {
        $body = $payload === [] ? '' : json_encode($payload, JSON_THROW_ON_ERROR);
        $signer = new ServiceDeskSigner($appKey, $secret);
        $headers = $signer->headersFor($method, $uri, $body);

        return $method === 'GET'
            ? test()->withHeaders($headers)->get($uri)
            : test()->withHeaders($headers)->json($method, $uri, $payload);
    };

    $this->actor = fn () => [
        'type' => User::class,
        'id' => $this->user->id,
        'name' => $this->user->name,
        'email' => $this->user->email,
    ];

    $this->createTicket = function () {
        return ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
            'department_id' => $this->department->id,
            'title' => 'Has attachments',
            'description' => 'Body',
            'actor' => ($this->actor)(),
        ])->json('data.uuid');
    };
});

it('uploads a base64-encoded attachment to a ticket it owns', function () {
    $uuid = ($this->createTicket)();
    $contents = base64_encode('hello world');

    $response = ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => $contents,
        'actor' => ($this->actor)(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.file_name', 'note.txt')
        ->assertJsonMissingPath('data.contents');
});

it('rejects an attachment upload for a ticket owned by a different app', function () {
    $uuid = ($this->createTicket)();

    ($this->call)('POST', 'satellite-2', 'secret-2', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => base64_encode('hello'),
        'actor' => ($this->actor)(),
    ])->assertStatus(404);
});

it('rejects invalid base64', function () {
    $uuid = ($this->createTicket)();

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => '***not base64***',
        'actor' => ($this->actor)(),
    ])->assertStatus(422);
});

it('rejects an attachment larger than the configured inline limit', function () {
    config()->set('service-desk.api.max_inline_attachment', 1);

    $uuid = ($this->createTicket)();
    $contents = base64_encode(str_repeat('a', 2048));

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => $contents,
        'actor' => ($this->actor)(),
    ])->assertStatus(422);
});

it('lists attachments without their contents', function () {
    $uuid = ($this->createTicket)();

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => base64_encode('hello'),
        'actor' => ($this->actor)(),
    ]);

    ($this->call)('GET', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonMissingPath('data.0.contents');
});

it('downloads a single attachment including its base64 contents', function () {
    $uuid = ($this->createTicket)();

    $stored = ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments", [
        'file_name' => 'note.txt',
        'mime_type' => 'text/plain',
        'contents' => base64_encode('hello world'),
        'actor' => ($this->actor)(),
    ]);

    $attachmentUuid = $stored->json('data.uuid');

    ($this->call)('GET', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/attachments/{$attachmentUuid}")
        ->assertOk()
        ->assertJsonPath('data.contents', base64_encode('hello world'));
});
