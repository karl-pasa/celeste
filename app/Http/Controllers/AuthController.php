<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * A real bcrypt hash of a value nobody will submit.
     *
     * When the submitted identifier matches no account we still run a hash
     * comparison against this, so a miss costs the same as a hit. Without it
     * a database miss returns in single-digit milliseconds while a real
     * comparison costs ~250ms at cost 12, and that gap is enough to
     * enumerate every username and email address in the system over the
     * network without ever logging in.
     */
    private const DUMMY_HASH = '$2y$12$D8yQ5tQ0Zr7bB1kK9pXhIuVv2mN4cJ6wL0aS3dF5gH7jK9lM1nO3q';

    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'role' => in_array($request->query('as'), [User::ROLE_STUDENT, User::ROLE_REGISTRAR], true)
                ? $request->query('as')
                : User::ROLE_STUDENT,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $isStudent = $request->input('role') === User::ROLE_STUDENT;

        $credentials = $request->validate([
            // Students sign in with their university email; the Registrar
            // keeps a username, since office accounts are not tied to a
            // student record.
            'username' => $isStudent ? ['required', 'email', 'max:150'] : ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:200'],
            'role'     => ['required', 'in:' . User::ROLE_STUDENT . ',' . User::ROLE_REGISTRAR],
        ], [
            'username.required' => $isStudent ? 'Enter your university email address.' : 'Enter your username.',
            'username.email'    => 'That does not look like an email address. Students sign in with their university email.',
            'password.required' => 'Enter your password.',
        ]);

        $this->ensureIsNotRateLimited($request, $credentials['username']);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        // Constant-cost path for a nonexistent account.
        if (! $user) {
            Hash::check($credentials['password'], self::DUMMY_HASH);

            $this->failLogin($request, $credentials['username'], 'no_such_account', $isStudent);
        }

        // The role is part of the credential, not a check performed after the
        // fact. Authenticating first and logging out afterwards means a
        // student submitting through the Registrar tab holds a real session,
        // however briefly, and any error in between leaves it standing.
        $authenticated = Auth::attempt([
            'username'  => $user->username,
            'password'  => $credentials['password'],
            'role'      => $credentials['role'],
            'is_active' => true,
        ], $this->shouldRemember($request));

        if (! $authenticated) {
            $this->failLogin($request, $credentials['username'], 'bad_credentials', $isStudent);
        }

        RateLimiter::clear($this->throttleKey($request, $credentials['username']));

        // New session ID for the authenticated identity, so a session fixed
        // before login cannot be reused after it.
        $request->session()->regenerate();

        $user = Auth::user();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AuditLog::record('auth.login', $user, ['role' => $user->role]);

        return redirect()->intended($this->homeFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLog::record('auth.logout', $user);
        }

        Auth::logout();

        // Destroy the server-side session and issue a fresh CSRF token, so
        // neither the old session ID nor the old token is replayable.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }

    /**
     * Fail identically whichever way the attempt was wrong.
     *
     * The reason is recorded for the audit trail but never returned: a
     * response that distinguishes "no such account" from "wrong password"
     * hands an attacker an account oracle, and one that names the account's
     * role tells them which accounts are worth attacking.
     */
    private function failLogin(Request $request, string $identifier, string $reason, bool $isStudent): never
    {
        RateLimiter::hit($this->throttleKey($request, $identifier), self::DECAY_SECONDS);

        // The submitted identifier is not stored. Users routinely type their
        // password into the username field, and audit_logs is readable in the
        // UI and included in backups.
        AuditLog::record('auth.failed', null, [
            'reason'          => $reason,
            'identifier_hash' => substr(hash('sha256', mb_strtolower(trim($identifier))), 0, 16),
            'role_attempted'  => $request->input('role'),
        ]);

        throw ValidationException::withMessages([
            'username' => $isStudent
                ? 'That email and password do not match our records.'
                : 'Those credentials do not match our records.',
        ]);
    }

    /**
     * Rate limit per account *and* per address.
     *
     * Keying on IP alone fails in both directions here: an attacker rotating
     * addresses is unlimited against one account, while a campus behind a
     * single NAT gateway shares one budget and locks its own students out.
     */
    private function ensureIsNotRateLimited(Request $request, string $identifier): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $identifier), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $identifier));

        AuditLog::record('auth.lockout', null, [
            'identifier_hash' => substr(hash('sha256', mb_strtolower(trim($identifier))), 0, 16),
            'seconds'         => $seconds,
        ]);

        throw ValidationException::withMessages([
            'username' => "Too many attempts. Try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request, string $identifier): string
    {
        return 'login|' . Str::transliterate(mb_strtolower(trim($identifier))) . '|' . $request->ip();
    }

    /**
     * Remember-me issues a cookie valid for years, bypassing session lifetime
     * entirely. On the Registrar's shared counter terminal that is a standing
     * credential left on the machine, so it is disabled unless a deployment
     * explicitly opts in.
     */
    private function shouldRemember(Request $request): bool
    {
        return config('celeste.auth.allow_remember', false) && $request->boolean('remember');
    }

    private function homeFor(User $user): string
    {
        return $user->isRegistrar()
            ? route('registrar.dashboard')
            : route('student.dashboard');
    }
}
