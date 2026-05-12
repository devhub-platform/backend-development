<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\BlockedUserMiddleware;
use App\Http\Middleware\LogViewerMiddleware;
use Sentry\Laravel\Integration;
use Spatie\LaravelFlare\Facades\Flare;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'blocked.user' => BlockedUserMiddleware::class,
            'log-viewer' => LogViewerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Flare::handles($exceptions);
        Integration::handles($exceptions);
    })->create();