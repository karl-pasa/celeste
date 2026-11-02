<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a user out when the website has been left alone for too long.
 *
 * While the page is open and in front, the browser sends a small "still here"
 * message every few seconds (see the script in layouts/app.blade.php). When
 * the person switches away, minimises or closes the page, those messages stop.
 * If the next request arrives more than LIMIT seconds after the last one, the
 * session is ended here on the server, so it does not depend on the browser
 * behaving.
 */
class EnforceActivityWindow
{
    /** Seconds the website may be left before the session ends. */
    public const LIMIT = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $now  = time();
        $last = $request->session()->get('last_active_at');

        if ($last !== null && ($now - (int) $last) > self::LIMIT) {
            AuditLog::record('auth.idle_logout', $user, ['seconds_away' => $now - (int) $last]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['expired' => true], 401)
                : redirect()->route('login', ['timeout' => 1]);
        }

        $request->session()->put('last_active_at', $now);

        return $next($request);
    }
}