<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskApiClient;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidStatusTransitionException;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\Transports\ApiTicketTransport;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->transport = new ApiTicketTransport(new ServiceDeskApiClient('https://central.test', 'satellite-1', 'secret'));
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);

    // Matches TicketApiResource's exact output shape -- no id/user_type/user_id.
    $this->ticketPayload = fn (array $overrides = []) => array_merge([
        'uuid' => 'a1b2c3d4-0000-0000-0000-000000000000',
        'reference_number' => 'SD-00001',
        'department_id' => 1,
        'title' => 'Hi',
        'description' => 'Body',
        'status' => 'open',
        'priority' => 'medium',
        'source' => 'web',
        'requester_name' => 'John Doe',
        'requester_email' => 'john@example.com',
    ], $overrides);

    $this->fakeTicket = function (array $overrides = []) {
        $ticket = new Ticket;
        $ticket->forceFill(($this->ticketPayload)($overrides));
        $ticket->exists = true;

        return $ticket;
    };
});

it('creates a ticket via a signed POST and hydrates the response', function () {
    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)()], 201)]);

    $ticket = $this->transport->create(['department_id' => 1, 'title' => 'Hi', 'description' => 'Body'], $this->user);

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->exists)->toBeTrue()
        ->and($ticket->uuid)->toBe('a1b2c3d4-0000-0000-0000-000000000000')
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->user_name)->toBe('John Doe')
        ->and($ticket->user_email)->toBe('john@example.com');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://central.test/tickets'
            && $request['actor']['type'] === $this->user->getMorphClass()
            && $request['actor']['id'] === $this->user->id
            && $request['actor']['email'] === 'john@example.com';
    });
});

it('finds a ticket by uuid', function () {
    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)()], 200)]);

    $ticket = $this->transport->findByUuid('a1b2c3d4-0000-0000-0000-000000000000');

    expect($ticket->reference_number)->toBe('SD-00001');
});

it('throws TicketNotFoundException on a 404 when finding by uuid', function () {
    Http::fake(['central.test/*' => Http::response(['message' => 'not found'], 404)]);

    expect(fn () => $this->transport->findByUuid('missing'))->toThrow(TicketNotFoundException::class);
});

it('throws TicketNotFoundException on a 404 when finding by reference', function () {
    Http::fake(['central.test/*' => Http::response(['message' => 'not found'], 404)]);

    expect(fn () => $this->transport->findByReference('SD-99999'))->toThrow(TicketNotFoundException::class);
});

it('closes a ticket via the status endpoint', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)(['status' => 'closed'])], 200)]);

    $result = $this->transport->close($ticket, $this->user);

    expect($result->status)->toBe(TicketStatus::Closed);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://central.test/tickets/a1b2c3d4-0000-0000-0000-000000000000/status'
            && $request['status'] === 'closed';
    });
});

it('maps a 409 from the status endpoint to InvalidStatusTransitionException', function () {
    $ticket = ($this->fakeTicket)(['status' => 'closed']);

    Http::fake(['central.test/*' => Http::response(['message' => 'invalid transition'], 409)]);

    expect(fn () => $this->transport->reopen($ticket))->toThrow(InvalidStatusTransitionException::class);
});

it('refuses to change status to anything other than open/closed without calling the API', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)()], 200)]);

    expect(fn () => $this->transport->changeStatus($ticket, TicketStatus::InProgress))
        ->toThrow(ServiceDeskApiException::class);

    Http::assertNothingSent();
});

it('refuses assign, unassign, and delete without calling the API', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)()], 200)]);

    expect(fn () => $this->transport->assign($ticket, $this->user))->toThrow(ServiceDeskApiException::class);
    expect(fn () => $this->transport->unassign($ticket))->toThrow(ServiceDeskApiException::class);
    expect(fn () => $this->transport->delete($ticket))->toThrow(ServiceDeskApiException::class);

    Http::assertNothingSent();
});

it('updates non-status fields via a signed PATCH', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)(['title' => 'Updated'])], 200)]);

    $result = $this->transport->update($ticket, ['title' => 'Updated'], $this->user);

    expect($result->title)->toBe('Updated');

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request->url() === 'https://central.test/tickets/a1b2c3d4-0000-0000-0000-000000000000'
            && $request['title'] === 'Updated';
    });
});

it('routes a status key inside update() through the status endpoint instead of a generic PATCH', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ($this->ticketPayload)(['status' => 'closed'])], 200)]);

    $result = $this->transport->update($ticket, ['status' => TicketStatus::Closed]);

    expect($result->status)->toBe(TicketStatus::Closed);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/status'));
});

it('uploads an attachment as base64', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ['uuid' => 'att-1', 'file_name' => 'note.txt']], 201)]);

    $result = $this->transport->uploadAttachment($ticket, 'note.txt', 'text/plain', 'hello world', $this->user);

    expect($result['file_name'])->toBe('note.txt');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://central.test/tickets/a1b2c3d4-0000-0000-0000-000000000000/attachments'
            && $request['contents'] === base64_encode('hello world')
            && $request['file_name'] === 'note.txt';
    });
});

it('lists attachments', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => [['uuid' => 'att-1'], ['uuid' => 'att-2']]], 200)]);

    expect($this->transport->listAttachments($ticket))->toHaveCount(2);
});

it('downloads and decodes an attachment', function () {
    $ticket = ($this->fakeTicket)();

    Http::fake(['central.test/*' => Http::response(['data' => ['contents' => base64_encode('hello world')]], 200)]);

    expect($this->transport->downloadAttachment($ticket, 'att-1'))->toBe('hello world');
});
