<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\Api\TicketApiController;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyServiceDeskApiSignature;

Route::prefix(config('service-desk.api.prefix', 'service-desk/api'))
    ->middleware([
        ...config('service-desk.api.middleware', []),
        VerifyServiceDeskApiSignature::class,
    ])
    ->group(function () {
        Route::get('tickets', [TicketApiController::class, 'index'])
            ->name('service-desk.api.tickets.index');

        Route::post('tickets', [TicketApiController::class, 'store'])
            ->name('service-desk.api.tickets.store');

        Route::get('tickets/by-reference/{reference}', [TicketApiController::class, 'showByReference'])
            ->name('service-desk.api.tickets.show-by-reference');

        Route::get('tickets/{uuid}', [TicketApiController::class, 'show'])
            ->name('service-desk.api.tickets.show');

        Route::patch('tickets/{uuid}', [TicketApiController::class, 'update'])
            ->name('service-desk.api.tickets.update');

        Route::post('tickets/{uuid}/status', [TicketApiController::class, 'changeStatus'])
            ->name('service-desk.api.tickets.change-status');
    });
