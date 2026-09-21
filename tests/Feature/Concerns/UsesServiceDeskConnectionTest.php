<?php

use JeffersonGoncalves\ServiceDesk\Models\Category;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Models\TicketHistory;

it('uses the app default connection when service-desk.connection is unset', function () {
    config()->set('service-desk.connection', null);

    expect((new Ticket)->getConnectionName())->toBeNull();
});

it('uses the configured shared connection for every core service-desk model', function (string $class) {
    config()->set('service-desk.connection', 'shared-tickets');

    expect((new $class)->getConnectionName())->toBe('shared-tickets');
})->with([Ticket::class, TicketComment::class, TicketAttachment::class, TicketHistory::class, Department::class, Category::class]);

it('lets an explicit model-level connection override the shared config', function () {
    config()->set('service-desk.connection', 'shared-tickets');

    $ticket = new Ticket;
    $ticket->setConnection('explicit-override');

    expect($ticket->getConnectionName())->toBe('explicit-override');
});
