<?php

namespace App\Http\Middleware;

use App\Services\RexlCloud\RexlCloudClient;
use App\Support\CloudUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a RestModule login and refreshes the user's permissions on every page load,
 * so permission changes apply without logging in again.
 */
class EnsureCloudSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CloudUser::current() === null) {
            return redirect()->guest(route('login'));
        }

        CloudUser::update(RexlCloudClient::fromSession()->me());

        return $next($request);
    }
}
