@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Office of the University Registrar · ' . config('celeste.institution.short'))

@section('actions')
    <a href="{{ route('registrar.certificates.generate') }}" class="btn btn-sm btn-psu">
        <i class="bi bi-file-earmark-plus"></i> Generate a document
    </a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        ['Certificates on file', number_format($summary['total_certificates']), $summary['issued_this_period'] . ' issued in the last 30 days', 'bi-collection'],
        ['Active', number_format($summary['active_certificates']), $summary['revoked_certificates'] . ' revoked', 'bi-patch-check'],
        ['Verifications (30d)', number_format($summary['verifications']), null, 'bi-search'],
        ['Checks that passed', $summary['success_rate'] . '%', $summary['failed'] . ' did not resolve', 'bi-shield-check'],
    ] as [$label, $value, $meta, $icon])
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-label">{{ $label }}</div>
                    <i class="bi {{ $icon }} text-muted-celeste"></i>
                </div>
                <div class="stat-value">{{ $value }}</div>
                <div class="stat-meta">
                    @if ($label === 'Verifications (30d)')
                        <span class="{{ $summary['verifications_change'] >= 0 ? 'stat-up' : 'stat-down' }}">
                            <i class="bi bi-arrow-{{ $summary['verifications_change'] >= 0 ? 'up' : 'down' }}-right"></i>
                            {{ abs($summary['verifications_change']) }}%
                        </span>
                        against the previous 30 days
                    @else
                        {{ $meta }}
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card-celeste mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Verification activity, last 14 days</span>
                <a href="{{ route('registrar.analytics') }}" class="btn btn-sm btn-psu-outline">Full analytics</a>
            </div>
            <div class="p-3">
                <canvas id="volumeChart" height="110"></canvas>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recently issued</span>
                <a href="{{ route('registrar.certificates') }}" class="btn btn-sm btn-psu-outline">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-celeste">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Document</th>
                            <th>Issued to</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $certificate)
                            <tr>
                                <td class="serial">{{ $certificate->serial_number }}</td>
                                <td><span class="badge-celeste badge-type">{{ $certificate->type_label }}</span></td>
                                <td>{{ $certificate->studentRecord?->full_name }}</td>
                                <td class="text-muted-celeste">{{ $certificate->issued_on?->format('M j, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('registrar.certificates.show', $certificate) }}" class="btn btn-sm btn-psu-outline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                    <h6>No documents issued yet</h6>
                                    <p>Generate the first one and it will appear here.</p>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-celeste mb-3">
            <div class="card-header">Needs your attention</div>
            <div class="p-3">
                @foreach ($flags as $flag)
                    <div class="flag flag-{{ $flag['severity'] }}">
                        <span class="flag-dot"></span>
                        <div>
                            <h6>{{ $flag['title'] }}</h6>
                            <p>{{ $flag['detail'] }}</p>
                            <p class="flag-action"><i class="bi bi-arrow-return-right"></i> {{ $flag['action'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Latest verifications</div>
            <div class="p-3">
                @forelse ($activity as $log)
                    <div class="d-flex justify-content-between align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div style="min-width:0">
                            <div class="text-truncate" style="font-size:.8125rem">
                                {{ $log->certificate?->serial_number ?? $log->submitted_reference }}
                            </div>
                            <div class="text-muted-celeste" style="font-size:.75rem">
                                {{ \App\Models\VerificationLog::methods()[$log->method] ?? $log->method }}
                                · {{ $log->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <span class="badge-celeste {{ $log->resultBadge() }}">{{ ucfirst(str_replace('_', ' ', $log->result)) }}</span>
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-activity"></i></div>
                        <h6>No checks recorded</h6>
                        <p>Verification attempts will show up here as they happen.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('volumeChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($series['labels']),
            datasets: [
                {
                    label: 'Passed',
                    data: @json($series['authentic']),
                    borderColor: '#22a94a',
                    backgroundColor: 'rgba(34,169,74,.12)',
                    fill: true, tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                },
                {
                    label: 'Failed',
                    data: @json($series['failed']),
                    borderColor: '#c9354a',
                    backgroundColor: 'rgba(201,53,74,.1)',
                    fill: true, tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { align: 'end', labels: { boxWidth: 8, usePointStyle: true, pointStyle: 'circle', font: { family: 'Poppins', size: 11 } } },
                tooltip: { backgroundColor: '#12224f', padding: 10, titleFont: { family: 'Poppins' }, bodyFont: { family: 'Poppins' } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 }, color: '#8a94ad', maxTicksLimit: 8 } },
                y: { beginAtZero: true, grid: { color: '#e3e8f1' }, ticks: { font: { family: 'Poppins', size: 11 }, color: '#8a94ad', precision: 0 } },
            },
        },
    });
</script>
@endpush
