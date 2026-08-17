<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Email verification for student accounts.
 *
 * Student accounts are provisioned in bulk from a CSV. A typo'd or
 * attacker-supplied address produces a working account bound to a mailbox
 * nobody controls — and since the initial password is the student number,
 * which is printed on every document, that account is trivially reachable.
 * Verification is what ties the account to the person.
 *
 * Registrar accounts are created in person at a console by someone with
 * server access and are marked verified at creation.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                $request->user()->isRegistrar() ? route('registrar.dashboard') : route('student.dashboard')
            );
        }

        return view('auth.verify-email');
    }

    /**
     * The signed URL is validated by EmailVerificationRequest before this
     * runs: signature, expiry, and that the link belongs to the signed-in
     * user. A link forwarded to someone else will not verify for them.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            AuditLog::record('auth.email_verified', $request->user());
        }

        return redirect()
            ->intended($request->user()->isRegistrar() ? route('registrar.dashboard') : route('student.dashboard'))
            ->with('status', 'Your email address has been verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back();
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your university email address.');
    }
}
