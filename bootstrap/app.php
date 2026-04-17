<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\BlockedUserMiddleware;
use App\Http\Middleware\LogViewerMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'blocked.user' => BlockedUserMiddleware::class,
            'log-viewer' => LogViewerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Spatie\LaravelFlare\Facades\Flare::handles($exceptions);
    })->create();
