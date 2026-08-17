<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Rules\PasswordPolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Password reset, and the forced first-login change.
 *
 * Token lifetime and request throttling are configured in config/auth.php
 * (15 minutes, 60 seconds between requests). Laravel's broker stores the token
 * hashed, so a database read does not yield a usable link.
 */
class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Always reports success, whatever the outcome.
     *
     * Telling the visitor whether an address is registered turns this endpoint
     * into an account oracle — the same enumeration weakness the login form
     * was hardened against, reintroduced by a friendlier error message.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:150']]);

        $status = Password::sendResetLink($request->only('email'));

        AuditLog::record('auth.reset_requested', null, [
            'email_hash' => substr(hash('sha256', mb_strtolower(trim($request->input('email')))), 0, 16),
            'status'     => $status,
        ]);

        return back()->with('status', 'If that address is registered, a reset link is on its way. The link expires in 15 minutes.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(
            ['token' => ['required'], 'email' => ['required', 'email'], 'password' => PasswordPolicy::rules()],
            PasswordPolicy::messages()
        );

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $this->assertNotForbidden($user, $password);

                $user->forceFill([
                    'password'            => Hash::make($password),
                    'password_changed_at' => now(),
                    'remember_token'      => Str::random(60),
                ])->save();

                AuditLog::record('auth.password_reset', $user);

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'That reset link is invalid or has expired. Request a new one.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with the new one.');
    }

    // ---- forced first-login change -------------------------------------

    public function changeForm(Request $request): View
    {
        return view('auth.change-password', [
            'forced' => $request->user()->password_changed_at === null,
        ]);
    }

    public function change(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate(
            ['current_password' => ['required', 'current_password'], 'password' => PasswordPolicy::rules()],
            PasswordPolicy::messages() + ['current_password.current_password' => 'That is not your current password.']
        );

        $this->assertNotForbidden($user, $request->input('password'));

        $user->forceFill([
            'password'            => Hash::make($request->input('password')),
            'password_changed_at' => now(),
        ])->save();

        // Evict every other live session. Someone changing their password
        // because they believe it is compromised should not leave the other
        // party signed in.
        Auth::logoutOtherDevices($request->input('password'));

        AuditLog::record('auth.password_changed', $user);

        return redirect()
            ->route($user->isRegistrar() ? 'registrar.dashboard' : 'student.dashboard')
            ->with('status', 'Your password has been changed.');
    }

    /**
     * Refuse values that are public knowledge about the account holder.
     */
    private function assertNotForbidden(User $user, string $password): void
    {
        foreach (PasswordPolicy::forbidden($user) as $forbidden) {
            if (mb_strtolower(trim($password)) === mb_strtolower(trim((string) $forbidden))) {
                throw ValidationException::withMessages([
                    'password' => 'That value is printed on your documents or is part of your account. Choose something else.',
                ]);
            }
        }
    }
}
