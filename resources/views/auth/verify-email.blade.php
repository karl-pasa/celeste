@extends('layouts.guest')

@section('title', 'Verify your email address')

@section('content')
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="{{ asset('images/psu-seal.png') }}" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university">{{ config('celeste.institution.name') }}</p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Verify your email address</p>
    </div>

    <div class="auth-body">
        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small mb-3">{{ session('status') }}</div>
        @endif

        <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
            We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
            Open it to confirm this address belongs to you. Verification is what ties your
            account to you rather than to whoever holds that mailbox.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-envelope-arrow-up"></i> Send another link
            </button>
        </form>

        <p class="text-muted-celeste mt-3 mb-0" style="font-size:.75rem">
            Wrong address? Your email comes from your student record, so the Office of the
            University Registrar changes it — contact them at
            {{ config('celeste.institution.registrar_email') }}.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-psu-outline w-100">Sign out</button>
        </form>
    </div>

    <div class="auth-foot">&copy; {{ date('Y') }} {{ config('celeste.institution.name') }}.</div>
</div>
@endsection
