<?php $__env->startSection('title', 'Verification result'); ?>

<?php
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
?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center pt-4">
    <div class="col-lg-7 col-xl-6">
        <div class="result-card">
            <div class="result-banner result-<?php echo e($result); ?>">
                <div class="result-icon"><i class="bi <?php echo e($icon); ?>"></i></div>
                <h2><?php echo e($heading); ?></h2>
                <p><?php echo e($outcome['message']); ?></p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate): ?>
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Document</dt><dd><?php echo e($certificate->type_label); ?></dd></div>
                        <div class="detail-row"><dt>Serial number</dt><dd class="serial"><?php echo e($certificate->serial_number); ?></dd></div>
                        <div class="detail-row"><dt>Issued to</dt><dd><?php echo e($certificate->studentRecord?->full_name); ?></dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial"><?php echo e($certificate->studentRecord?->student_number); ?></dd></div>
                        <div class="detail-row"><dt>Program</dt><dd><?php echo e($certificate->studentRecord?->program); ?></dd></div>
                        <div class="detail-row"><dt>College</dt><dd><?php echo e($certificate->studentRecord?->college); ?></dd></div>
                        <div class="detail-row"><dt>Date issued</dt><dd><?php echo e($certificate->issued_on?->format('F j, Y')); ?></dd></div>
                        <div class="detail-row"><dt>Issued by</dt><dd><?php echo e(config('celeste.institution.name')); ?></dd></div>
                        <div class="detail-row"><dt>Fingerprint</dt><dd><span class="hash-chip"><?php echo e($certificate->shortHash()); ?></span></dd></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->revocation_reason): ?>
                            <div class="detail-row"><dt>Reason</dt><dd><?php echo e($certificate->revocation_reason); ?></dd></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>
            <?php else: ?>
                <div class="p-3 p-md-4">
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        Reference checked: <span class="hash-chip"><?php echo e($reference); ?></span>
                    </p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="p-3 p-md-4 pt-0 d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('verify')); ?>" class="btn btn-psu flex-fill">
                    <i class="bi bi-arrow-repeat"></i> Check another document
                </a>
                <a href="<?php echo e(route('verify.scanner')); ?>" class="btn btn-psu-outline flex-fill">
                    <i class="bi bi-qr-code-scan"></i> Scan a code
                </a>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($result, ['tampered', 'revoked'], true)): ?>
            <div class="mt-3 p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
                <h6 class="text-white mb-1" style="font-size:.875rem"><i class="bi bi-exclamation-triangle"></i> What to do next</h6>
                <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.8125rem">
                    Do not accept this copy as proof. Report it to the Office of the University Registrar at
                    <?php echo e(config('celeste.institution.registrar_email')); ?>, quoting the reference above.
                </p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/public/result.blade.php ENDPATH**/ ?>