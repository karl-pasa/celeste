<?php $__env->startSection('title', 'My documents'); ?>
<?php $__env->startSection('subtitle', 'Everything the Registrar has issued to you'); ?>

<?php $__env->startSection('content'); ?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $record): ?>
    <div class="card-celeste">
        <div class="empty">
            <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
            <h6>We could not find your student record</h6>
            <p>Your account is not yet linked to a record. Contact the Office of the University Registrar
               at <?php echo e(config('celeste.institution.registrar_email')); ?> to have it connected.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card-celeste h-100">
                <div class="card-header">Your record</div>
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Name</dt><dd><?php echo e($record->full_name); ?></dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial"><?php echo e($record->student_number); ?></dd></div>
                        <div class="detail-row"><dt>College</dt><dd><?php echo e($record->college); ?></dd></div>
                        <div class="detail-row"><dt>Program</dt><dd><?php echo e($record->program); ?></dd></div>
                        <div class="detail-row"><dt>Status</dt><dd class="text-capitalize"><?php echo e($record->status); ?></dd></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->date_graduated): ?>
                            <div class="detail-row"><dt>Graduated</dt><dd><?php echo e($record->date_graduated->format('F j, Y')); ?></dd></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-celeste h-100">
                <div class="card-header">Sharing a document</div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-2" style="font-size:.875rem">
                        Send the PDF as it is. The QR code on it lets an employer or school confirm it is real
                        without contacting the Registrar.
                    </p>
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        A printed copy works the same way — the code survives photocopying.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-celeste">
        <div class="card-header">Issued documents</div>
        <div class="table-responsive">
            <table class="table table-celeste">
                <thead>
                    <tr><th>Document</th><th>Serial</th><th>Issued</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($certificate->type_label); ?></td>
                            <td class="serial"><?php echo e($certificate->serial_number); ?></td>
                            <td class="text-muted-celeste"><?php echo e($certificate->issued_on?->format('M j, Y')); ?></td>
                            <td>
                                <span class="badge-celeste <?php echo e(match ($certificate->status) {
                                    'issued' => 'badge-issued', 'revoked' => 'badge-revoked', default => 'badge-superseded',
                                }); ?>"><?php echo e(ucfirst($certificate->status)); ?></span>
                            </td>
                            <td class="text-end">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'issued'): ?>
                                    <a href="<?php echo e(route('certificates.download', $certificate)); ?>" class="btn btn-sm btn-psu">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="<?php echo e(route('certificates.print', $certificate)); ?>" target="_blank" class="btn btn-sm btn-psu-outline">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted-celeste" style="font-size:.8125rem">Not available</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5">
                            <div class="empty">
                                <div class="empty-icon"><i class="bi bi-file-earmark"></i></div>
                                <h6>No documents yet</h6>
                                <p>Request a document at the Office of the University Registrar. It will appear here once issued.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/student/dashboard.blade.php ENDPATH**/ ?>