<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a password change while password_changed_at is null.
 *
 * Students are provisioned with their student number as the initial password.
 * A policy that only applies to people who volunteer to change is not a
 * control; this is what turns "you should change it" into "you have".
 */
class RequirePasswordChange
{
    /** Routes reachable while the change is outstanding. */
    private const EXEMPT = [
        'password.change',
        'password.change.store',
        'logout',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('celeste.auth.require_password_change', true)) {
            return $next($request);
        }

        if ($user
            && $user->password_changed_at === null
            && ! $request->routeIs(self::EXEMPT)
            && ! $request->expectsJson()) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
