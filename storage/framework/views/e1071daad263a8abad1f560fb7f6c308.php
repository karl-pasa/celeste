<?php $__env->startSection('title', 'All certificates'); ?>
<?php $__env->startSection('subtitle', 'Search, verify integrity, revoke, or reissue any document on file'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('registrar.certificates.generate')); ?>" class="btn btn-sm btn-psu">
        <i class="bi bi-file-earmark-plus"></i> Generate
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('certificates.certificate-table');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2593044529-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/certificates.blade.php ENDPATH**/ ?>