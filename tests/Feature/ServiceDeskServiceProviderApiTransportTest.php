<?php

use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;
use JeffersonGoncalves\ServiceDesk\Services\Transports\ApiTicketTransport;
use JeffersonGoncalves\ServiceDesk\Services\Transports\DatabaseTicketTransport;

it('resolves TicketTransport to DatabaseTicketTransport by default', function () {
    expect(app(TicketTransport::class))->toBeInstanceOf(DatabaseTicketTransport::class);
});

it('resolves TicketTransport to ApiTicketTransport when configured', function () {
    config()->set('service-desk.ticket.transport', 'api');
    config()->set('service-desk.api.url', 'https://central.test');
    config()->set('service-desk.api.app_key', 'satellite-1');
    config()->set('service-desk.api.secret', 'secret');

    expect(app(TicketTransport::class))->toBeInstanceOf(ApiTicketTransport::class);
});

it('throws a clear configuration error when the api transport is selected without credentials', function () {
    config()->set('service-desk.ticket.transport', 'api');
    config()->set('service-desk.api.url', null);
    config()->set('service-desk.api.app_key', null);
    config()->set('service-desk.api.secret', null);

    expect(fn () => app(TicketTransport::class))->toThrow(ServiceDeskApiException::class);
});
