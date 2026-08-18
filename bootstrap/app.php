<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCertificateAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RequirePasswordChange;
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
            // Binds the session to the authenticated user's password hash, so
            // changing a password invalidates every other live session.
            AuthenticateSession::class,

            // is_active is verified once, at login. Without this a user
            // deactivated mid-session stays signed in until it expires.
            EnsureAccountIsActive::class,

            // While password_changed_at is null the account is still on its
            // provisioned credential, which for students is public data.
            RequirePasswordChange::class,

            // Signed-in pages must not be stored by the browser, or Back would
            // replay a registrar's dashboard after they have signed out.
            PreventBackHistory::class,
        ]);

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
