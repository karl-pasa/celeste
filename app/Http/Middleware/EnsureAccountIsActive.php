<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-checks is_active on every authenticated request.
 *
 * The flag is verified inside Auth::attempt, which runs once — at login. A
 * user deactivated mid-session therefore stays signed in until the session
 * expires, which for a registrar account being revoked for cause is exactly
 * the window that matters. Revoking access has to actually revoke access.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            AuditLog::record('auth.deactivated_session_ended', $user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['username' => 'This account has been deactivated. Contact the Office of the University Registrar.']);
        }

        return $next($request);
    }
}
