@extends('layouts.guest')

@section('title', 'Reset your password')

@section('content')
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="{{ asset('images/psu-seal.png') }}" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university">{{ config('celeste.institution.name') }}</p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Reset your password</p>
    </div>

    <div class="auth-body">
        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
        @endif

        <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
            Enter the email address on your account and we will send a reset link.
            The link expires in 15 minutes.
        </p>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="yourname@{{ config('celeste.student_email_domain') }}" required autofocus>
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-send"></i> Send reset link
            </button>
        </form>

        <div class="divider-label my-3">or</div>
        <a href="{{ route('login') }}" class="btn btn-psu-outline w-100">Back to sign in</a>
    </div>

    <div class="auth-foot">&copy; {{ date('Y') }} {{ config('celeste.institution.name') }}.</div>
</div>
@endsection
