<?php

use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;
use JeffersonGoncalves\ServiceDesk\Models\Department;
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
            'title' => 'Has comments',
            'description' => 'Body',
            'actor' => ($this->actor)(),
        ])->json('data.uuid');
    };
});

it('adds a reply comment to a ticket it owns', function () {
    $uuid = ($this->createTicket)();

    $response = ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/comments", [
        'body' => 'Any update?',
        'actor' => ($this->actor)(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.body', 'Any update?')
        ->assertJsonMissingPath('data.is_internal')
        ->assertJsonMissingPath('data.type');
});

it('rejects a comment for a ticket owned by a different app', function () {
    $uuid = ($this->createTicket)();

    ($this->call)('POST', 'satellite-2', 'secret-2', "/service-desk/api/tickets/{$uuid}/comments", [
        'body' => 'Any update?',
        'actor' => ($this->actor)(),
    ])->assertStatus(404);
});

it('rejects a comment without a body', function () {
    $uuid = ($this->createTicket)();

    ($this->call)('POST', 'satellite-1', 'secret-1', "/service-desk/api/tickets/{$uuid}/comments", [
        'actor' => ($this->actor)(),
    ])->assertStatus(422);
});
