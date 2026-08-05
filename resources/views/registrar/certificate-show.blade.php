@extends('layouts.app')

@section('title', $certificate->serial_number)
@section('subtitle', $certificate->type_label . ' · issued ' . $certificate->issued_on?->format('F j, Y'))

@section('actions')
    <a href="{{ route('certificates.print', $certificate) }}" target="_blank" class="btn btn-sm btn-psu-outline">
        <i class="bi bi-printer"></i> Print
    </a>
    <a href="{{ route('certificates.download', $certificate) }}" class="btn btn-sm btn-psu">
        <i class="bi bi-download"></i> Download
    </a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        {{-- Integrity banner --}}
        <div class="card-celeste mb-3">
            <div class="p-3 d-flex align-items-center gap-3"
                 style="border-left:3px solid {{ $hashIntact ? 'var(--psu-green)' : 'var(--psu-red)' }};border-radius:var(--radius-lg)">
                <i class="bi {{ $hashIntact ? 'bi-shield-check' : 'bi-shield-exclamation' }}"
                   style="font-size:1.5rem;color:{{ $hashIntact ? 'var(--psu-green)' : 'var(--psu-red)' }}"></i>
                <div>
                    <h6 class="mb-1">{{ $hashIntact ? 'Fingerprint intact' : 'Fingerprint mismatch' }}</h6>
                    <p class="mb-0 text-muted-celeste" style="font-size:.8125rem">
                        {{ $hashIntact
                            ? 'The stored record still hashes to the value issued with this document.'
                            : 'The stored record no longer matches its original fingerprint. This document will fail public verification — investigate before reissuing.' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card-celeste mb-3">
            <div class="card-header">Document record</div>
            <div class="p-3 p-md-4">
                <dl class="mb-0">
                    <div class="detail-row"><dt>Serial number</dt><dd class="serial">{{ $certificate->serial_number }}</dd></div>
                    <div class="detail-row"><dt>Document type</dt><dd>{{ $certificate->type_label }}</dd></div>
                    <div class="detail-row"><dt>Status</dt><dd>
                        <span class="badge-celeste {{ match ($certificate->status) {
                            'issued' => 'badge-issued', 'revoked' => 'badge-revoked', default => 'badge-superseded',
                        } }}">{{ ucfirst($certificate->status) }}</span>
                    </dd></div>
                    <div class="detail-row"><dt>Issued to</dt><dd>{{ $certificate->studentRecord?->full_name }}</dd></div>
                    <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $certificate->studentRecord?->student_number }}</dd></div>
                    <div class="detail-row"><dt>Program</dt><dd>{{ $certificate->studentRecord?->program }}</dd></div>
                    <div class="detail-row"><dt>College</dt><dd>{{ $certificate->studentRecord?->college }}</dd></div>
                    <div class="detail-row"><dt>Issued by</dt><dd>{{ $certificate->issuer?->name }}</dd></div>
                    <div class="detail-row"><dt>Generated</dt><dd>{{ $certificate->created_at->format('M j, Y g:i A') }}</dd></div>
                    @if ($certificate->batch)
                        <div class="detail-row"><dt>Batch</dt><dd>{{ $certificate->batch->reference }} — {{ $certificate->batch->label }}</dd></div>
                    @endif
                    @if ($certificate->revocation_reason)
                        <div class="detail-row"><dt>Reason</dt><dd>{{ $certificate->revocation_reason }}</dd></div>
                    @endif
                    <div class="detail-row"><dt>Times verified</dt><dd>{{ number_format($certificate->verification_count) }}</dd></div>
                    <div class="detail-row"><dt>Last verified</dt><dd>{{ $certificate->last_verified_at?->diffForHumans() ?? 'Never' }}</dd></div>
                </dl>

                <div class="mt-3">
                    <div class="form-label">Content fingerprint (SHA-256)</div>
                    <div class="hash-chip d-block mb-2">{{ $certificate->content_hash }}</div>
                    <div class="form-label">File fingerprint</div>
                    <div class="hash-chip d-block">{{ $certificate->file_hash ?? 'Not yet rendered' }}</div>
                </div>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Verification history</div>
            <div class="table-responsive">
                <table class="table table-celeste">
                    <thead>
                        <tr><th>Result</th><th>Method</th><th>IP</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($certificate->verificationLogs()->latest()->limit(20)->get() as $log)
                            <tr>
                                <td><span class="badge-celeste {{ $log->resultBadge() }}">{{ ucfirst(str_replace('_', ' ', $log->result)) }}</span></td>
                                <td class="text-muted-celeste">{{ \App\Models\VerificationLog::methods()[$log->method] ?? $log->method }}</td>
                                <td class="text-muted-celeste">{{ $log->ip_address }}</td>
                                <td class="text-muted-celeste">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                                    <h6>Not verified yet</h6>
                                    <p>Checks against this document will be listed here.</p>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-celeste mb-3">
            <div class="card-header">Verification QR</div>
            <div class="p-3 p-md-4 text-center">
                <img src="{{ $qr }}" alt="QR code for {{ $certificate->serial_number }}" class="img-fluid mb-3" style="max-width:200px">
                <p class="text-muted-celeste mb-2" style="font-size:.8125rem">This code is printed on the document.</p>
                <div class="hash-chip d-block mb-3" style="word-break:break-all">{{ $certificate->verificationUrl() }}</div>
                <a href="{{ $certificate->verificationUrl() }}" target="_blank" class="btn btn-psu-outline btn-sm w-100">
                    <i class="bi bi-box-arrow-up-right"></i> Open the public result
                </a>
                <a href="{{ route('certificates.qr', $certificate) }}" download class="btn btn-psu-outline btn-sm w-100 mt-2">
                    <i class="bi bi-download"></i> Download the QR image
                </a>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Hashed payload</div>
            <div class="p-3">
                <p class="text-muted-celeste mb-2" style="font-size:.8125rem">
                    These are the exact values the fingerprint covers.
                </p>
                <dl class="mb-0">
                    @foreach ($certificate->payload as $key => $value)
                        @continue(is_array($value))
                        <div class="detail-row">
                            <dt style="font-size:.8125rem">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                            <dd style="font-size:.8125rem">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
