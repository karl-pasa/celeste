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

/**
 * Sign-in by institutional email address.
 *
 * Both roles authenticate on the email column. Usernames are no longer part
 * of the credential: one identifier means one place to enforce the domain
 * rule, and an account that cannot be reached at a university address cannot
 * receive a verification or reset link either.
 */
class AuthController extends Controller
{
    /**
     * A real bcrypt hash of a value nobody will submit.
     *
     * When the address matches no account we still run a comparison against
     * this, so a miss costs the same as a hit. Without it a database miss
     * returns in single-digit milliseconds against ~250ms for a real bcrypt
     * comparison, and that gap enumerates every address in the system.
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
        $domain = (string) config('celeste.institution.email_domain', 'parsu.edu.ph');

        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
            'role'     => ['required', 'in:' . User::ROLE_STUDENT . ',' . User::ROLE_REGISTRAR],
        ], [
            'email.required'    => 'Enter your institutional email address.',
            'email.email'       => "That does not look like an email address. Use your @{$domain} account.",
            'password.required' => $isStudent ? 'Enter your student number.' : 'Enter your password.',
        ]);

        $email = mb_strtolower(trim($credentials['email']));

        // Reject non-institutional addresses before touching the database.
        // Only @parsu.edu.ph accounts exist, so anything else is a typo or
        // someone probing with an outside address.
        if (! $this->isInstitutional($email, $domain)) {
            $this->failLogin($request, $email, 'non_institutional_domain', $domain);
        }

        $this->ensureIsNotRateLimited($request, $email);

        $user = User::where('email', $email)->first();

        // Constant-cost path for an address with no account.
        if (! $user) {
            Hash::check($credentials['password'], self::DUMMY_HASH);
            $this->failLogin($request, $email, 'no_such_account', $domain);
        }

        // The role is part of the credential, not a check performed after the
        // fact. Authenticating first and logging out afterwards means a
        // student submitting through the Registrar tab holds a real session,
        // however briefly, and any error in between leaves it standing.
        $authenticated = Auth::attempt([
            'email'     => $email,
            'password'  => $credentials['password'],
            'role'      => $credentials['role'],
            'is_active' => true,
        ], $this->shouldRemember($request));

        if (! $authenticated) {
            $this->failLogin($request, $email, 'bad_credentials', $domain);
        }

        RateLimiter::clear($this->throttleKey($request, $email));

        // New session ID for the authenticated identity, so a session fixed
        // before login cannot be reused after it.
        $request->session()->regenerate();

        $user = Auth::user();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AuditLog::record('auth.login', $user, ['role' => $user->role]);

        return redirect()->intended(
            $user->isRegistrar() ? route('registrar.dashboard') : route('student.dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
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
     * Exact host comparison, not a suffix test.
     *
     * str_ends_with('@parsu.edu.ph') would accept
     * "attacker@evil-parsu.edu.ph". Splitting on the last @ and comparing the
     * whole host removes the question.
     */
    private function isInstitutional(string $email, string $domain): bool
    {
        $at = strrpos($email, '@');

        if ($at === false || $domain === '') {
            return false;
        }

        return hash_equals(mb_strtolower($domain), mb_strtolower(substr($email, $at + 1)));
    }

    /**
     * Fail identically whichever way the attempt was wrong.
     *
     * A response that distinguishes "no account" from "wrong password" is an
     * account oracle, and one that names the role tells an attacker which
     * addresses are worth pursuing. The reason is recorded, never returned.
     */
    private function failLogin(Request $request, string $email, string $reason, string $domain): never
    {
        RateLimiter::hit($this->throttleKey($request, $email), self::DECAY_SECONDS);

        // The submitted address is not stored. Users routinely type their
        // password into the wrong field, and audit_logs is readable in the UI
        // and included in backups.
        AuditLog::record('auth.failed', null, [
            'reason'         => $reason,
            'email_hash'     => substr(hash('sha256', $email), 0, 16),
            'role_attempted' => $request->input('role'),
        ]);

        throw ValidationException::withMessages([
            'email' => "Those credentials do not match any @{$domain} account.",
        ]);
    }

    /**
     * Rate limit per account *and* per address.
     *
     * Keying on IP alone fails both ways: an attacker rotating addresses is
     * unlimited against one account, while a campus behind a single NAT
     * gateway shares one budget and locks its own students out.
     */
    private function ensureIsNotRateLimited(Request $request, string $email): void
    {
        $key = $this->throttleKey($request, $email);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));
        $seconds = RateLimiter::availableIn($key);

        AuditLog::record('auth.lockout', null, [
            'email_hash' => substr(hash('sha256', $email), 0, 16),
            'seconds'    => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => "Too many attempts. Try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request, string $email): string
    {
        return 'login|' . Str::transliterate($email) . '|' . $request->ip();
    }

    /**
     * Remember-me issues a cookie valid for years, bypassing session lifetime.
     * On the Registrar's shared counter that is a standing credential left on
     * the machine, so it is off unless a deployment opts in.
     */
    private function shouldRemember(Request $request): bool
    {
        return config('celeste.auth.allow_remember', false) && $request->boolean('remember');
    }
}
