<?php $__env->startSection('title', 'Audit trail'); ?>
<?php $__env->startSection('subtitle', 'Every generation, download, revocation, and sign-in recorded by CELESTE'); ?>

<?php $__env->startSection('content'); ?>
<div class="card-celeste">
    <div class="table-responsive">
        <table class="table table-celeste">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>By</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="badge-celeste badge-type"><?php echo e($audit->action); ?></span></td>
                        <td><?php echo e($audit->user?->name ?? 'Guest'); ?></td>
                        <td class="text-muted-celeste" style="font-size:.8125rem;max-width:280px">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($audit->context): ?>
                                <?php echo e(collect($audit->context)->map(fn ($v, $k) => "$k: " . (is_array($v) ? json_encode($v) : $v))->implode(' · ')); ?>

                            <?php else: ?>
                                —
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="text-muted-celeste"><?php echo e($audit->created_at->format('M j, Y g:i A')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4">
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-journal-text"></i></div>
                            <h6>The trail is empty</h6>
                            <p>Actions taken in CELESTE will be recorded here.</p>
                        </div>
                    </td></tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top"><?php echo e($audits->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/logs.blade.php ENDPATH**/ ?>