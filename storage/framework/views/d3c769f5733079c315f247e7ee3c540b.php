<?php $__env->startSection('title', 'Verification analytics'); ?>
<?php $__env->startSection('subtitle', 'Who is checking documents, which ones, and what the pattern suggests'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('analytics.analytics-dashboard');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1867335727-0', $__key);

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/analytics.blade.php ENDPATH**/ ?>