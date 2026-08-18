@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="{{ asset('images/psu-seal.png') }}" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university">{{ config('celeste.institution.name') }}</p>
        <p class="campus">{{ config('celeste.institution.campus') }}</p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Certificate Authentication and Verification System</p>
        <p class="system-note">
            Issued documents carry a QR code and a cryptographic hash.
            Anyone can check a document without an account.
        </p>
    </div>

    <div class="auth-body">
        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" x-data="{ role: '{{ old('role', $role) }}' }">
            @csrf

            <div class="role-tabs mb-3" role="tablist" aria-label="Account type">
                @foreach ([
                    'student'   => ['Student', 'bi-mortarboard'],
                    'registrar' => ['Registrar', 'bi-shield-lock'],
                ] as $value => [$label, $icon])
                    <button type="button" role="tab"
                            class="role-tab" :class="{ 'active': role === '{{ $value }}' }"
                            :aria-selected="role === '{{ $value }}'"
                            @click="role = '{{ $value }}'">
                        <i class="bi {{ $icon }}"></i>{{ $label }}
                    </button>
                @endforeach
            </div>

            <input type="hidden" name="role" :value="role">

            {{-- Both roles sign in with an institutional address; only the
                 password hint differs. --}}
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope"></i> Institutional email
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       :placeholder="role === 'student'
                            ? 'jdelacruz922.pbox@{{ config('celeste.institution.email_domain', 'parsu.edu.ph') }}'
                            : 'registrar@{{ config('celeste.institution.email_domain', 'parsu.edu.ph') }}'"
                       autocomplete="username" autofocus required>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" x-data="{ show: false }">
                <label for="password" class="form-label">
                    <i class="bi bi-lock"></i>
                    <span x-text="role === 'student' ? 'Student number' : 'Password'">Student number</span>
                </label>
                <div class="input-group">
                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                           class="form-control border-end-0 @error('password') is-invalid @enderror"
                           :placeholder="role === 'student' ? 'e.g. 2021-00184' : 'Enter your password'"
                           autocomplete="current-password" required>
                    <button class="input-group-text" type="button" @click="show = !show"
                            :aria-label="show ? 'Hide password' : 'Show password'">
                        <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <p class="text-muted-celeste mb-3" style="font-size:.75rem" x-show="role === 'student'" x-cloak>
                Sign in with your university email. Your password is your student number
                until you set your own.
            </p>

            @if (config('celeste.auth.allow_remember', false))
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted-celeste" for="remember">Keep me signed in</label>
                </div>
            @endif

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-box-arrow-in-right"></i> Sign in
            </button>
        </form>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="text-muted-celeste" style="font-size:.8125rem">
                    Forgot your password?
                </a>
            </div>
        @endif

        <div class="divider-label my-3">or</div>

        <a href="{{ route('verify') }}" class="btn btn-psu-outline w-100">
            <i class="bi bi-patch-check"></i> Verify a document without signing in
        </a>
    </div>

    <div class="auth-foot">
        &copy; {{ date('Y') }} {{ config('celeste.institution.name') }}. All rights reserved.
    </div>
</div>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
