<?php

use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // *** Register the 'guest' middleware alias ***
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            // Add other aliases here if needed, e.g.:
//           'auth' => \App\Http\Middleware\Authenticate::class,
             'can' => Authorize::class,
             'verified' => EnsureEmailIsVerified::class,
            // etc.
        ]);

        // You can also add global middleware, groups, etc. here if necessary
        // Example:
        // $middleware->web(append: [
        //     \App\Http\Middleware\ExampleMiddleware::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling configuration...
    })->create();
