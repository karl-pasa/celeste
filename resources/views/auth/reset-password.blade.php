@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="{{ asset('images/psu-seal.png') }}" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university">{{ config('celeste.institution.name') }}</p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Choose a new password</p>
    </div>

    <div class="auth-body">
        <form method="POST" action="{{ route('password.store') }}" x-data="{ show: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                       class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                    It cannot be your student number, which is printed on your documents.
                </p>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label"><i class="bi bi-key-fill"></i> Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-shield-lock"></i> Reset password
            </button>
        </form>
    </div>

    <div class="auth-foot">&copy; {{ date('Y') }} {{ config('celeste.institution.name') }}.</div>
</div>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
