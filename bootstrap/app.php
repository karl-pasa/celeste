<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCertificateAccess;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Session\Middleware\AuthenticateSession;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AuthenticateSession::class,

            EnsureAccountIsActive::class,

            PreventBackHistory::class,
        ]);

        $middleware->alias([
            'role'            => EnsureUserHasRole::class,
            'certificate.own' => EnsureCertificateAccess::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
