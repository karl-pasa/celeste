<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('subtitle', 'Office of the University Registrar · ' . config('celeste.institution.short')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('registrar.certificates.generate')); ?>" class="btn btn-sm btn-psu">
        <i class="bi bi-file-earmark-plus"></i> Generate a document
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3 mb-3">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
        ['Certificates on file', number_format($summary['total_certificates']), $summary['issued_this_period'] . ' issued in the last 30 days', 'bi-collection'],
        ['Active', number_format($summary['active_certificates']), $summary['revoked_certificates'] . ' revoked', 'bi-patch-check'],
        ['Verifications (30d)', number_format($summary['verifications']), null, 'bi-search'],
        ['Checks that passed', $summary['success_rate'] . '%', $summary['failed'] . ' did not resolve', 'bi-shield-check'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $meta, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-label"><?php echo e($label); ?></div>
                    <i class="bi <?php echo e($icon); ?> text-muted-celeste"></i>
                </div>
                <div class="stat-value"><?php echo e($value); ?></div>
                <div class="stat-meta">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label === 'Verifications (30d)'): ?>
                        <span class="<?php echo e($summary['verifications_change'] >= 0 ? 'stat-up' : 'stat-down'); ?>">
                            <i class="bi bi-arrow-<?php echo e($summary['verifications_change'] >= 0 ? 'up' : 'down'); ?>-right"></i>
                            <?php echo e(abs($summary['verifications_change'])); ?>%
                        </span>
                        against the previous 30 days
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Verification activity, last 14 days</span>
                <a href="<?php echo e(route('registrar.analytics')); ?>" class="btn btn-sm btn-psu-outline">Full analytics</a>
            </div>
            <div class="p-3">
                <canvas id="volumeChart" height="110"></canvas>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recently issued</span>
                <a href="<?php echo e(route('registrar.certificates')); ?>" class="btn btn-sm btn-psu-outline">View all</a>
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
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="serial"><?php echo e($certificate->serial_number); ?></td>
                                <td><span class="badge-celeste badge-type"><?php echo e($certificate->type_label); ?></span></td>
                                <td><?php echo e($certificate->studentRecord?->full_name); ?></td>
                                <td class="text-muted-celeste"><?php echo e($certificate->issued_on?->format('M j, Y')); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('registrar.certificates.show', $certificate)); ?>" class="btn btn-sm btn-psu-outline">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                    <h6>No documents issued yet</h6>
                                    <p>Generate the first one and it will appear here.</p>
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
            <div class="card-header">Needs your attention</div>
            <div class="p-3">
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
            <div class="card-header">Latest verifications</div>
            <div class="p-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $activity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="d-flex justify-content-between align-items-start py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                        <div style="min-width:0">
                            <div class="text-truncate" style="font-size:.8125rem">
                                <?php echo e($log->certificate?->serial_number ?? $log->submitted_reference); ?>

                            </div>
                            <div class="text-muted-celeste" style="font-size:.75rem">
                                <?php echo e(\App\Models\VerificationLog::methods()[$log->method] ?? $log->method); ?>

                                · <?php echo e($log->created_at->diffForHumans()); ?>

                            </div>
                        </div>
                        <span class="badge-celeste <?php echo e($log->resultBadge()); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $log->result))); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-activity"></i></div>
                        <h6>No checks recorded</h6>
                        <p>Verification attempts will show up here as they happen.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const ctx = document.getElementById('volumeChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($series['labels'], 15, 512) ?>,
            datasets: [
                {
                    label: 'Passed',
                    data: <?php echo json_encode($series['authentic'], 15, 512) ?>,
                    borderColor: '#22a94a',
                    backgroundColor: 'rgba(34,169,74,.12)',
                    fill: true, tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                },
                {
                    label: 'Failed',
                    data: <?php echo json_encode($series['failed'], 15, 512) ?>,
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/dashboard.blade.php ENDPATH**/ ?>