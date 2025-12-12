<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// your custom middleware
use App\Http\Middleware\ApiTokenMiddleware;
// CORS middleware (built into Laravel)
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 🔹 Enable CORS globally (so Vue at :5173 can call Laravel at :8000)
        $middleware->append(HandleCors::class);

        // 🔹 Register alias so we can use middleware('auth.api') in routes/api.php
        $middleware->alias([
            'auth.api' => ApiTokenMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
