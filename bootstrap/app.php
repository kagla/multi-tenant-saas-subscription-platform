<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            $host = $request->getHost();
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $portSuffix = in_array($port, [80, 443]) ? '' : ':' . $port;

            return $scheme . '://' . $host . $portSuffix . '/login';
        });

        $middleware->alias([
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'tenant.rate' => \App\Http\Middleware\TenantRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
