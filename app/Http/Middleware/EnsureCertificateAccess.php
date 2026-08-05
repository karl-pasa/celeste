<?php

namespace App\Http\Middleware;

use App\Models\Certificate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A learner may only reach their own documents. The registrar may reach all of them.
 */
class EnsureCertificateAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $certificate = $request->route('certificate');
        $user = $request->user();

        if (! $certificate instanceof Certificate || ! $user) {
            abort(404);
        }

        if ($user->isRegistrar()) {
            return $next($request);
        }

        $owns = $certificate->studentRecord?->student_number === $user->student_number;

        abort_unless($owns, 403, 'This document belongs to another student.');

        return $next($request);
    }
}
