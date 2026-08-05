@extends('layouts.public')

@section('title', 'Verification result')

@php
    $certificate = $outcome['certificate'];
    $result = $outcome['result'];
    $heading = match ($result) {
        'authentic' => 'Authentic document',
        'revoked'   => 'No longer valid',
        'tampered'  => 'Does not match our records',
        default     => 'Not on file',
    };
    $icon = match ($result) {
        'authentic' => 'bi-patch-check-fill',
        'revoked'   => 'bi-slash-circle',
        'tampered'  => 'bi-exclamation-octagon-fill',
        default     => 'bi-question-circle',
    };
@endphp

@section('content')
<div class="row justify-content-center pt-4">
    <div class="col-lg-7 col-xl-6">
        <div class="result-card">
            <div class="result-banner result-{{ $result }}">
                <div class="result-icon"><i class="bi {{ $icon }}"></i></div>
                <h2>{{ $heading }}</h2>
                <p>{{ $outcome['message'] }}</p>
            </div>

            @if ($certificate)
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Document</dt><dd>{{ $certificate->type_label }}</dd></div>
                        <div class="detail-row"><dt>Serial number</dt><dd class="serial">{{ $certificate->serial_number }}</dd></div>
                        <div class="detail-row"><dt>Issued to</dt><dd>{{ $certificate->studentRecord?->full_name }}</dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $certificate->studentRecord?->student_number }}</dd></div>
                        <div class="detail-row"><dt>Program</dt><dd>{{ $certificate->studentRecord?->program }}</dd></div>
                        <div class="detail-row"><dt>College</dt><dd>{{ $certificate->studentRecord?->college }}</dd></div>
                        <div class="detail-row"><dt>Date issued</dt><dd>{{ $certificate->issued_on?->format('F j, Y') }}</dd></div>
                        <div class="detail-row"><dt>Issued by</dt><dd>{{ config('celeste.institution.name') }}</dd></div>
                        <div class="detail-row"><dt>Fingerprint</dt><dd><span class="hash-chip">{{ $certificate->shortHash() }}</span></dd></div>
                        @if ($certificate->revocation_reason)
                            <div class="detail-row"><dt>Reason</dt><dd>{{ $certificate->revocation_reason }}</dd></div>
                        @endif
                    </dl>
                </div>
            @else
                <div class="p-3 p-md-4">
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        Reference checked: <span class="hash-chip">{{ $reference }}</span>
                    </p>
                </div>
            @endif

            <div class="p-3 p-md-4 pt-0 d-flex gap-2 flex-wrap">
                <a href="{{ route('verify') }}" class="btn btn-psu flex-fill">
                    <i class="bi bi-arrow-repeat"></i> Check another document
                </a>
                <a href="{{ route('verify.scanner') }}" class="btn btn-psu-outline flex-fill">
                    <i class="bi bi-qr-code-scan"></i> Scan a code
                </a>
            </div>
        </div>

        @if (in_array($result, ['tampered', 'revoked'], true))
            <div class="mt-3 p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
                <h6 class="text-white mb-1" style="font-size:.875rem"><i class="bi bi-exclamation-triangle"></i> What to do next</h6>
                <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.8125rem">
                    Do not accept this copy as proof. Report it to the Office of the University Registrar at
                    {{ config('celeste.institution.registrar_email') }}, quoting the reference above.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
