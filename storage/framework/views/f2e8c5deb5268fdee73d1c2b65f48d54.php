<?php $__env->startSection('title', 'Student records'); ?>
<?php $__env->startSection('subtitle', 'The source data every generated document is built from'); ?>

<?php $__env->startSection('content'); ?>
<div class="card-celeste">
    <div class="table-responsive">
        <table class="table table-celeste">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Number</th>
                    <th>College</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = \App\Models\StudentRecord::withCount('certificates')->orderBy('last_name')->paginate(20); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($record->full_name); ?></td>
                        <td class="serial"><?php echo e($record->student_number); ?></td>
                        <td class="text-muted-celeste"><?php echo e($record->college); ?></td>
                        <td class="text-muted-celeste"><?php echo e($record->program); ?></td>
                        <td><span class="badge-celeste badge-type text-capitalize"><?php echo e($record->status); ?></span></td>
                        <td><?php echo e($record->certificates_count); ?></td>
                        <td class="text-end">
                            <a href="<?php echo e(route('registrar.certificates')); ?>?q=<?php echo e($record->student_number); ?>"
                               class="btn btn-sm btn-psu-outline">View documents</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7">
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-people"></i></div>
                            <h6>No student records loaded</h6>
                            <p>Run the seeder or import records before generating documents.</p>
                        </div>
                    </td></tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/students.blade.php ENDPATH**/ ?>