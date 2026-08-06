<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the browser serving a signed-in page from cache after sign-out.
 *
 * Ending a session invalidates it on the server, but the browser has already
 * stored the HTML it rendered while the session was live. Pressing Back
 * replays that stored copy without contacting the server at all, so a
 * registrar's dashboard — student names, serial numbers, verification history
 * — stays readable on a shared machine after they have signed out and walked
 * away. Nothing is authenticated at that point; it is simply an old page.
 *
 * The headers below tell the browser it may not store the response, so Back
 * has to re-request it. That request has no session, the auth middleware
 * catches it, and the person lands on the sign-in page.
 *
 * `no-store` is the one that matters. `no-cache` alone permits storage with
 * revalidation, and it is `no-store` that also opts the page out of the
 * back-forward cache in Chrome, Firefox, and Safari, which would otherwise
 * restore a whole live page from memory.
 *
 * Only applied to responses rendered for a signed-in user. The public
 * verification portal carries nothing private and stays cacheable.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Auth::check()) {
            return $response;
        }

        return $response->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma'        => 'no-cache',
            'Expires'       => 'Sat, 01 Jan 2000 00:00:00 GMT',
        ]);
    }
}