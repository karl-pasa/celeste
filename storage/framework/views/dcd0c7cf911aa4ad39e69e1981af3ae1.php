<?php $__env->startSection('title', 'Verify a document'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center pt-4 pt-lg-5">
    <div class="col-lg-7 col-xl-6 text-center mb-4">
        <span class="badge-celeste mb-3" style="background:rgba(255,255,255,.14);color:#fff">
            <i class="bi bi-shield-check"></i> Office of the University Registrar
        </span>
        <h1 class="text-white fw-bold mb-2" style="font-size:2rem;letter-spacing:-.01em">
            Check a document from <?php echo e(config('celeste.institution.short')); ?>

        </h1>
        <p class="mb-0" style="color:rgba(255,255,255,.75);font-size:.9375rem">
            Every diploma, transcript, certificate of enrolment, and honorable dismissal issued through CELESTE
            carries a QR code and a cryptographic fingerprint. Scan the code or type the serial number printed
            on the document — no account needed.
        </p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('verification.verify-panel');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3800975925-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    </div>
</div>

<div class="row justify-content-center mt-5">
    <div class="col-lg-9">
        <div class="row g-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                ['bi-qr-code-scan', 'Scan the code', 'Point your camera at the QR in the lower corner of the document. It opens this page with the result already loaded.'],
                ['bi-fingerprint', 'We recompute the fingerprint', 'CELESTE re-hashes the record on file and compares it to the fingerprint issued with the document.'],
                ['bi-patch-check', 'You get a plain answer', 'Authentic, revoked, altered, or not on file — with the holder details we can show you.'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$icon, $title, $body]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-4">
                    <div class="h-100 p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="step-pill"><?php echo e($i + 1); ?></span>
                            <i class="bi <?php echo e($icon); ?>" style="color:rgba(255,255,255,.7)"></i>
                        </div>
                        <h6 class="text-white mb-1" style="font-size:.9375rem"><?php echo e($title); ?></h6>
                        <p class="mb-0" style="color:rgba(255,255,255,.66);font-size:.8125rem;line-height:1.55"><?php echo e($body); ?></p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>

<div class="row justify-content-center mt-4">
    <div class="col-lg-9 text-center">
        <p class="mb-0" style="color:rgba(255,255,255,.5);font-size:.8125rem">
            A document that fails verification should not be accepted. Report it to the Office of the University
            Registrar at <?php echo e(config('celeste.institution.registrar_email')); ?>.
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/public/portal.blade.php ENDPATH**/ ?>