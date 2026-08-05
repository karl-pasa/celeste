<div wire:key="analytics-{{ $days }}">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <p class="text-muted-celeste mb-0" style="font-size:.875rem">
            Every verification attempt against a {{ config('celeste.institution.short') }} document, including the ones that failed.
        </p>
        <div class="role-tabs" style="width:auto">
            @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $value => $label)
                <button type="button" wire:click="setPeriod({{ $value }})"
                        class="role-tab {{ $days === $value ? 'active' : '' }}" style="padding:.4rem .9rem">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Verifications', number_format($summary['verifications']), 'change'],
            ['Passed', number_format($summary['authentic']), $summary['success_rate'] . '% of all checks'],
            ['Did not resolve', number_format($summary['failed']), 'Altered, revoked, or not on file'],
            ['Documents issued', number_format($summary['issued_this_period']), 'In this period'],
        ] as [$label, $value, $meta])
            <div class="col-6 col-xl-3">
                <div class="stat">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-value">{{ $value }}</div>
                    <div class="stat-meta">
                        @if ($meta === 'change')
                            <span class="{{ $summary['verifications_change'] >= 0 ? 'stat-up' : 'stat-down' }}">
                                <i class="bi bi-arrow-{{ $summary['verifications_change'] >= 0 ? 'up' : 'down' }}-right"></i>
                                {{ abs($summary['verifications_change']) }}%
                            </span>
                            against the previous period
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
                <div class="card-header">Verification volume</div>
                <div class="p-3"><canvas id="analyticsVolume" height="105"></canvas></div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <div class="card-celeste h-100">
                        <div class="card-header">Checks by document type</div>
                        <div class="p-3"><canvas id="analyticsType" height="170"></canvas></div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card-celeste h-100">
                        <div class="card-header">How people verified</div>
                        <div class="p-3">
                            @forelse ($byMethod as $method => $count)
                                @php $share = $summary['verifications'] > 0 ? round(($count / $summary['verifications']) * 100) : 0; @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between" style="font-size:.8125rem">
                                        <span>{{ \App\Models\VerificationLog::methods()[$method] ?? $method }}</span>
                                        <span class="text-muted-celeste">{{ number_format($count) }} · {{ $share }}%</span>
                                    </div>
                                    <div class="progress mt-1" style="height:6px;background:var(--psu-navy-050)">
                                        <div class="progress-bar" style="width:{{ $share }}%;background:var(--psu-navy-600)"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-bar-chart"></i></div>
                                    <h6>No checks in this period</h6>
                                    <p>Widen the date range to see earlier activity.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-celeste">
                <div class="card-header">Verification log</div>
                <div class="table-responsive">
                    <table class="table table-celeste">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Document</th>
                                <th>Method</th>
                                <th>Result</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activity as $log)
                                <tr wire:key="log-{{ $log->id }}">
                                    <td class="serial">{{ $log->certificate?->serial_number ?? Str::limit($log->submitted_reference, 24) }}</td>
                                    <td class="text-muted-celeste">
                                        {{ $log->document_type ? (\App\Models\Certificate::types()[$log->document_type] ?? '—') : '—' }}
                                    </td>
                                    <td class="text-muted-celeste">{{ \App\Models\VerificationLog::methods()[$log->method] ?? $log->method }}</td>
                                    <td><span class="badge-celeste {{ $log->resultBadge() }}">{{ ucfirst(str_replace('_', ' ', $log->result)) }}</span></td>
                                    <td class="text-muted-celeste">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="bi bi-journal"></i></div>
                                        <h6>Nothing logged yet</h6>
                                        <p>Checks made through the public portal will appear here.</p>
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
                <div class="card-header">
                    Institutional decision support
                </div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
                        Patterns worth acting on, read from the same verification data.
                    </p>
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
                <div class="card-header">Most-checked documents</div>
                <div class="p-3">
                    @forelse ($mostChecked as $certificate)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div style="min-width:0">
                                <div class="serial text-truncate">{{ $certificate->serial_number }}</div>
                                <div class="text-muted-celeste text-truncate" style="font-size:.75rem">
                                    {{ $certificate->studentRecord?->full_name }}
                                </div>
                            </div>
                            <span class="badge-celeste badge-type">{{ $certificate->verification_count }}×</span>
                        </div>
                    @empty
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-graph-up"></i></div>
                            <h6>No documents verified yet</h6>
                            <p>Counts start once the first check comes in.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const paint = () => {
            ['analyticsVolume', 'analyticsType'].forEach(id => {
                const existing = Chart.getChart(id);
                if (existing) existing.destroy();
            });

            const font = { family: 'Poppins', size: 11 };

            new Chart(document.getElementById('analyticsVolume'), {
                type: 'line',
                data: {
                    labels: @json($series['labels']),
                    datasets: [
                        { label: 'Passed', data: @json($series['authentic']), borderColor: '#22a94a',
                          backgroundColor: 'rgba(34,169,74,.12)', fill: true, tension: .35, borderWidth: 2, pointRadius: 0 },
                        { label: 'Failed', data: @json($series['failed']), borderColor: '#c9354a',
                          backgroundColor: 'rgba(201,53,74,.1)', fill: true, tension: .35, borderWidth: 2, pointRadius: 0 },
                    ],
                },
                options: {
                    responsive: true, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { align: 'end', labels: { boxWidth: 8, usePointStyle: true, pointStyle: 'circle', font } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font, color: '#8a94ad', maxTicksLimit: 10 } },
                        y: { beginAtZero: true, grid: { color: '#e3e8f1' }, ticks: { font, color: '#8a94ad', precision: 0 } },
                    },
                },
            });

            new Chart(document.getElementById('analyticsType'), {
                type: 'bar',
                data: {
                    labels: @json(collect($byType)->pluck('label')),
                    datasets: [{
                        data: @json(collect($byType)->pluck('total')),
                        backgroundColor: ['#12224f', '#24417f', '#1d6fd0', '#22a94a'],
                        borderRadius: 6, barThickness: 26,
                    }],
                },
                options: {
                    indexAxis: 'y', responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#e3e8f1' }, ticks: { font, color: '#8a94ad', precision: 0 } },
                        y: { grid: { display: false }, ticks: { font, color: '#5b6784' } },
                    },
                },
            });
        };

        paint();
        Livewire.hook('morph.updated', () => paint());
    </script>
    @endscript
</div>
