<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPostmarkSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUsername = config('service-desk.email.inbound.postmark.webhook_username');
        $expectedPassword = config('service-desk.email.inbound.postmark.webhook_password');

        if (! $expectedUsername || ! $expectedPassword) {
            abort(500, 'Postmark webhook credentials are not configured.');
        }

        $username = $request->getUser();
        $password = $request->getPassword();

        if ($username !== $expectedUsername || $password !== $expectedPassword) {
            abort(403, 'Invalid Postmark webhook credentials.');
        }

        return $next($request);
    }
}
