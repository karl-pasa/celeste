@extends('layouts.guest')

@section('title', $forced ? 'Change your password' : 'Change password')

@section('content')
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="{{ asset('images/psu-seal.png') }}" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university">{{ config('celeste.institution.name') }}</p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">{{ $forced ? 'Set your own password' : 'Change your password' }}</p>
    </div>

    <div class="auth-body">
        @if ($forced)
            <div class="alert d-flex gap-2 py-2 px-3 mb-3"
                 style="background:var(--psu-gold-soft);border:1px solid #f0dcae;color:#8a5c0c;font-size:.8125rem">
                <i class="bi bi-exclamation-triangle"></i>
                <span>
                    You are still using the password your account was created with. For students that is
                    your student number, which is printed on your ID and on every document issued to you —
                    so anyone holding one of your documents could sign in as you. Choose your own password
                    to continue.
                </span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.store') }}" x-data="{ show: false }">
            @csrf

            <div class="mb-3">
                <label for="current_password" class="form-label"><i class="bi bi-lock"></i> Current password</label>
                <input type="password" id="current_password" name="current_password"
                       class="form-control @error('current_password') is-invalid @enderror"
                       autocomplete="current-password" required autofocus>
                @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label"><i class="bi bi-key"></i> New password</label>
                <div class="input-group">
                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                           class="form-control border-end-0 @error('password') is-invalid @enderror"
                           autocomplete="new-password" required>
                    <button class="input-group-text" type="button" @click="show = !show">
                        <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <p class="text-muted-celeste mt-2 mb-0" style="font-size:.75rem">
                    At least 10 characters, with upper and lower case letters and a number.
                </p>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label"><i class="bi bi-key-fill"></i> Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-shield-lock"></i> Change password
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-psu-outline w-100">Sign out instead</button>
        </form>
    </div>

    <div class="auth-foot">&copy; {{ date('Y') }} {{ config('celeste.institution.name') }}.</div>
</div>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
