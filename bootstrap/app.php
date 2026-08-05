<?php

use App\Http\Middleware\EnsureCertificateAccess;
use App\Http\Middleware\EnsureUserHasRole;
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
        $middleware->alias([
            'role'            => EnsureUserHasRole::class,
            'certificate.own' => EnsureCertificateAccess::class,
        ]);

        // Vercel and most PaaS hosts sit behind a proxy. Without this, Laravel
        // builds http:// URLs and every QR code points at the wrong scheme.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
