<div wire:key="analytics-<?php echo e($days); ?>">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <p class="text-muted-celeste mb-0" style="font-size:.875rem">
            Every verification attempt against a <?php echo e(config('celeste.institution.short')); ?> document, including the ones that failed.
        </p>
        <div class="role-tabs" style="width:auto">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" wire:click="setPeriod(<?php echo e($value); ?>)"
                        class="role-tab <?php echo e($days === $value ? 'active' : ''); ?>" style="padding:.4rem .9rem">
                    <?php echo e($label); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
            ['Verifications', number_format($summary['verifications']), 'change'],
            ['Passed', number_format($summary['authentic']), $summary['success_rate'] . '% of all checks'],
            ['Did not resolve', number_format($summary['failed']), 'Altered, revoked, or not on file'],
            ['Documents issued', number_format($summary['issued_this_period']), 'In this period'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $meta]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-6 col-xl-3">
                <div class="stat">
                    <div class="stat-label"><?php echo e($label); ?></div>
                    <div class="stat-value"><?php echo e($value); ?></div>
                    <div class="stat-meta">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meta === 'change'): ?>
                            <span class="<?php echo e($summary['verifications_change'] >= 0 ? 'stat-up' : 'stat-down'); ?>">
                                <i class="bi bi-arrow-<?php echo e($summary['verifications_change'] >= 0 ? 'up' : 'down'); ?>-right"></i>
                                <?php echo e(abs($summary['verifications_change'])); ?>%
                            </span>
                            against the previous period
                        <?php else: ?>
                            <?php echo e($meta); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $byMethod; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php $share = $summary['verifications'] > 0 ? round(($count / $summary['verifications']) * 100) : 0; ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between" style="font-size:.8125rem">
                                        <span><?php echo e(\App\Models\VerificationLog::methods()[$method] ?? $method); ?></span>
                                        <span class="text-muted-celeste"><?php echo e(number_format($count)); ?> · <?php echo e($share); ?>%</span>
                                    </div>
                                    <div class="progress mt-1" style="height:6px;background:var(--psu-navy-050)">
                                        <div class="progress-bar" style="width:<?php echo e($share); ?>%;background:var(--psu-navy-600)"></div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-bar-chart"></i></div>
                                    <h6>No checks in this period</h6>
                                    <p>Widen the date range to see earlier activity.</p>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr wire:key="log-<?php echo e($log->id); ?>">
                                    <td class="serial"><?php echo e($log->certificate?->serial_number ?? Str::limit($log->submitted_reference, 24)); ?></td>
                                    <td class="text-muted-celeste">
                                        <?php echo e($log->document_type ? (\App\Models\Certificate::types()[$log->document_type] ?? '—') : '—'); ?>

                                    </td>
                                    <td class="text-muted-celeste"><?php echo e(\App\Models\VerificationLog::methods()[$log->method] ?? $log->method); ?></td>
                                    <td><span class="badge-celeste <?php echo e($log->resultBadge()); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $log->result))); ?></span></td>
                                    <td class="text-muted-celeste"><?php echo e($log->created_at->diffForHumans()); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="bi bi-journal"></i></div>
                                        <h6>Nothing logged yet</h6>
                                        <p>Checks made through the public portal will appear here.</p>
                                    </div>
                                </td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $flags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flag flag-<?php echo e($flag['severity']); ?>">
                            <span class="flag-dot"></span>
                            <div>
                                <h6><?php echo e($flag['title']); ?></h6>
                                <p><?php echo e($flag['detail']); ?></p>
                                <p class="flag-action"><i class="bi bi-arrow-return-right"></i> <?php echo e($flag['action']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="card-celeste">
                <div class="card-header">Most-checked documents</div>
                <div class="p-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $mostChecked; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="d-flex justify-content-between align-items-center py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                            <div style="min-width:0">
                                <div class="serial text-truncate"><?php echo e($certificate->serial_number); ?></div>
                                <div class="text-muted-celeste text-truncate" style="font-size:.75rem">
                                    <?php echo e($certificate->studentRecord?->full_name); ?>

                                </div>
                            </div>
                            <span class="badge-celeste badge-type"><?php echo e($certificate->verification_count); ?>×</span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-graph-up"></i></div>
                            <h6>No documents verified yet</h6>
                            <p>Counts start once the first check comes in.</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

        <?php
        $__scriptKey = '3803170300-0';
        ob_start();
    ?>
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
                    labels: <?php echo json_encode($series['labels'], 15, 512) ?>,
                    datasets: [
                        { label: 'Passed', data: <?php echo json_encode($series['authentic'], 15, 512) ?>, borderColor: '#22a94a',
                          backgroundColor: 'rgba(34,169,74,.12)', fill: true, tension: .35, borderWidth: 2, pointRadius: 0 },
                        { label: 'Failed', data: <?php echo json_encode($series['failed'], 15, 512) ?>, borderColor: '#c9354a',
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
                    labels: <?php echo json_encode(collect($byType)->pluck('label'), 15, 512) ?>,
                    datasets: [{
                        data: <?php echo json_encode(collect($byType)->pluck('total'), 15, 512) ?>,
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
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div>
<?php /**PATH C:\laragon\www\celeste\resources\views/livewire/analytics/analytics-dashboard.blade.php ENDPATH**/ ?>