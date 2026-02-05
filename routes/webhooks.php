<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\MailgunWebhookController;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\PostmarkWebhookController;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\ResendWebhookController;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\SendGridWebhookController;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyMailgunSignature;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyPostmarkSignature;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyResendSignature;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifySendGridSignature;

Route::prefix(config('service-desk.webhooks.prefix', 'service-desk/webhooks'))
    ->middleware(config('service-desk.webhooks.middleware', []))
    ->group(function () {
        Route::post('mailgun', MailgunWebhookController::class)
            ->middleware(VerifyMailgunSignature::class)
            ->name('service-desk.webhooks.mailgun');

        Route::post('sendgrid', SendGridWebhookController::class)
            ->middleware(VerifySendGridSignature::class)
            ->name('service-desk.webhooks.sendgrid');

        Route::post('resend', ResendWebhookController::class)
            ->middleware(VerifyResendSignature::class)
            ->name('service-desk.webhooks.resend');

        Route::post('postmark', PostmarkWebhookController::class)
            ->middleware(VerifyPostmarkSignature::class)
            ->name('service-desk.webhooks.postmark');
    });
