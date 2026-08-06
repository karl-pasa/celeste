<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'role' => in_array($request->query('as'), ['student', 'registrar'], true)
                ? $request->query('as')
                : 'student',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role'     => ['required', 'in:student,registrar'],
        ]);

        $user = User::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        // Bcrypt comparison happens inside Auth::attempt; the role is checked
        // separately so a student cannot sign in through the registrar tab.
        $ok = $user
            && Auth::attempt([
                'username' => $user->username,
                'password' => $credentials['password'],
                'is_active' => true,
            ], $request->boolean('remember'));

        if (! $ok) {
            AuditLog::record('auth.failed', null, ['username' => $credentials['username']]);

            throw ValidationException::withMessages([
                'username' => 'Those credentials do not match our records.',
            ]);
        }

        if (Auth::user()->role !== $credentials['role']) {
            $actual = Auth::user()->roleLabel();
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => "This account signs in under {$actual}. Switch tabs and try again.",
            ]);
        }

        $request->session()->regenerate();

        Auth::user()->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AuditLog::record('auth.login', Auth::user(), ['role' => Auth::user()->role]);

        return redirect()->intended(
            Auth::user()->isRegistrar()
                ? route('registrar.dashboard')
                : route('student.dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record('auth.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }
}