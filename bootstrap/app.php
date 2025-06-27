<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'sanctum.stateful' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'custom.cors' => \App\Http\Middleware\CustomCors::class,
        ]);

        // Configure API middleware
        $middleware->api(remove: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Add CORS middleware
        $middleware->api(prepend: [
            \App\Http\Middleware\CustomCors::class,
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })


    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
