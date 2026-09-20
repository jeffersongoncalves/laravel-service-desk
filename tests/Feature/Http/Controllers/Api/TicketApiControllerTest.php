<?php

use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
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

        // getJson() JSON-encodes even an empty payload as the literal string
        // "[]" for the request body, which would mismatch what we signed
        // (a real GET carries no body) -- use the plain get() verb instead.
        return $method === 'GET'
            ? test()->withHeaders($headers)->get($uri)
            : test()->withHeaders($headers)->json($method, $uri, $payload);
    };

    $this->actor = fn (?User $user = null) => [
        'type' => User::class,
        'id' => ($user ?? $this->user)->id,
        'name' => ($user ?? $this->user)->name,
        'email' => ($user ?? $this->user)->email,
    ];
});

it('creates a ticket and stamps the caller app key onto it', function () {
    $response = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Hi',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Hi')
        ->assertJsonMissingPath('data.id');

    $ticket = Ticket::where('uuid', $response->json('data.uuid'))->firstOrFail();

    expect($ticket->app_key)->toBe('satellite-1');
});

it('rejects an actor type not in this app\'s allow-list', function () {
    ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Hi',
        'description' => 'Body',
        'actor' => ['type' => 'not-allowed-type', 'id' => 1],
    ])->assertStatus(422);
});

it('finds a created ticket by uuid, scoped to the creating app', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Findable',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');

    ($this->call)('GET', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', $uuid);
});

it('reports 404 for a ticket that belongs to a different app', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Owned by satellite-1',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');

    ($this->call)('GET', 'satellite-2', 'secret-2', "/service-desk/api/tickets/{$uuid}")
        ->assertStatus(404);
});

it('finds a ticket by reference number', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'By reference',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $reference = $created->json('data.reference_number');

    ($this->call)('GET', 'satellite-1', 'secret-1', "/service-desk/api/tickets/by-reference/{$reference}")
        ->assertOk()
        ->assertJsonPath('data.reference_number', $reference);
});

it('updates non-status fields', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Original',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');

    ($this->call)('PATCH', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}", [
        'title' => 'Updated',
        'actor' => ($this->actor)(),
    ])->assertOk()->assertJsonPath('data.title', 'Updated');
});

it('lets the requester close their own ticket', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Close me',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/status", [
        'status' => 'closed',
        'actor' => ($this->actor)(),
    ])->assertOk()->assertJsonPath('data.status', 'closed');
});

it('rejects an unrelated actor closing someone else\'s ticket', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Not yours',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');
    $stranger = User::create(['name' => 'Stranger', 'email' => 'stranger@example.com']);

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/status", [
        'status' => 'closed',
        'actor' => ($this->actor)($stranger),
    ])->assertStatus(403);
});

it('rejects an invalid status value at the route level', function () {
    $created = ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Status guard',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uuid = $created->json('data.uuid');

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/status", [
        'status' => 'in_progress',
        'actor' => ($this->actor)(),
    ])->assertStatus(422);
});

it('lists the caller\'s own tickets with pagination totals', function () {
    ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'First',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    ($this->call)('POST', 'satellite-1', 'secret-1', '/service-desk/api/tickets', [
        'department_id' => $this->department->id,
        'title' => 'Second',
        'description' => 'Body',
        'actor' => ($this->actor)(),
    ]);

    $uri = '/service-desk/api/tickets?actor[type]='.urlencode(User::class).'&actor[id]='.$this->user->id;

    ($this->call)('GET', 'satellite-1', 'secret-1', $uri)
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});
