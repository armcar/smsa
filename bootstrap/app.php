<?php

use App\Http\Middleware\ValidateWordPressMemberAreaRequest;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'wp.member-area' => ValidateWordPressMemberAreaRequest::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'integrations/wp/applications',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
