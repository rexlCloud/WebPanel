<?php

use App\Http\Middleware\EnsureCloudSession;
use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\ApiUnavailableException;
use App\Services\RexlCloud\UnauthenticatedException;
use App\Support\CloudUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['cloud.auth' => EnsureCloudSession::class]);
        // The panel runs behind a reverse proxy that terminates TLS
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (UnauthenticatedException $e, Request $request) {
            CloudUser::forget();

            return redirect()->guest(route('login'))->with('status', 'Your session has expired, please log in again.');
        });
        $exceptions->render(function (ApiUnavailableException $e, Request $request) {
            return response()->view('errors.unavailable', ['message' => $e->getMessage()], 503);
        });
        $exceptions->render(function (ApiException $e, Request $request) {
            if (in_array($e->status, [403, 404], true)) {
                abort($e->status, $e->getMessage());
            }
        });
    })->create();
