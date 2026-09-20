<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\CannedResponse;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\CannedResponseService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = new CannedResponseService;
    $this->requester = User::create(['name' => 'Requester Doe', 'email' => 'requester@example.com']);
    $this->operator = User::create(['name' => 'Operator Doe', 'email' => 'operator@example.com']);

    $this->ticket = Ticket::factory()->create([
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
        'title' => 'Cannot print',
        'status' => TicketStatus::InProgress,
        'priority' => TicketPriority::High,
    ]);
});

it('interpolates ticket variables', function () {
    $response = CannedResponse::create(['title' => 'Ack', 'body' => 'Re: {{ticket.reference}} - {{ticket.title}}']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe("Re: {$this->ticket->reference_number} - Cannot print");
});

it('interpolates status and priority using their labels', function () {
    $response = CannedResponse::create(['title' => 'Status', 'body' => 'Status: {{ticket.status}}, Priority: {{ticket.priority}}']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Status: '.TicketStatus::InProgress->label().', Priority: '.TicketPriority::High->label());
});

it('interpolates the requester from the ticket', function () {
    $response = CannedResponse::create(['title' => 'Greeting', 'body' => 'Hi {{requester.name}} ({{requester.email}})']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Hi Requester Doe (requester@example.com)');
});

it('interpolates the operator passed to render over the assigned operator', function () {
    $response = CannedResponse::create(['title' => 'Sign-off', 'body' => 'Regards, {{operator.name}}']);

    $rendered = $this->service->render($response, $this->ticket, $this->operator);

    expect($rendered)->toBe('Regards, Operator Doe');
});

it('falls back to the assigned operator when none is passed to render', function () {
    $this->ticket->update([
        'assigned_to_type' => $this->operator->getMorphClass(),
        'assigned_to_id' => $this->operator->id,
    ]);

    $response = CannedResponse::create(['title' => 'Sign-off', 'body' => 'Regards, {{operator.name}}']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Regards, Operator Doe');
});

it('renders an empty string for a known variable with no value', function () {
    $response = CannedResponse::create(['title' => 'Sign-off', 'body' => 'Regards, [{{operator.name}}]']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Regards, []');
});

it('leaves an unknown placeholder untouched rather than silently dropping it', function () {
    $response = CannedResponse::create(['title' => 'Typo', 'body' => 'Hi {{requestr.name}}']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Hi {{requestr.name}}');
});

it('registers and uses a custom resolver via resolveVariable', function () {
    $this->service->resolveVariable('company.name', fn () => 'Acme Inc');

    $response = CannedResponse::create(['title' => 'Footer', 'body' => 'Thanks, {{company.name}} Support']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Thanks, Acme Inc Support');
});

it('allows a custom resolver to override a default one', function () {
    $this->service->resolveVariable('ticket.title', fn () => 'Overridden Title');

    $response = CannedResponse::create(['title' => 'Override', 'body' => '{{ticket.title}}']);

    $rendered = $this->service->render($response, $this->ticket);

    expect($rendered)->toBe('Overridden Title');
});
