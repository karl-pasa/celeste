<?php $__env->startSection('document'); ?>
    <div class="doc-title">OFFICIAL TRANSCRIPT OF RECORDS</div>
    <div class="doc-sub">This transcript is invalid if it does not bear the seal of the University</div>

    <table class="fields">
        <tr>
            <td class="label">Name</td>
            <td class="value"><?php echo e($payload['full_name']); ?></td>
            <td class="label">Student number</td>
            <td class="value"><?php echo e($payload['student_number']); ?></td>
        </tr>
        <tr>
            <td class="label">College</td>
            <td class="value"><?php echo e($payload['college']); ?></td>
            <td class="label">Date admitted</td>
            <td class="value">
                <?php echo e(!empty($payload['date_admitted']) ? \Illuminate\Support\Carbon::parse($payload['date_admitted'])->format('F Y') : '—'); ?>

            </td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td class="value"><?php echo e($payload['program']); ?></td>
            <td class="label">Date graduated</td>
            <td class="value">
                <?php echo e(!empty($payload['date_graduated']) ? \Illuminate\Support\Carbon::parse($payload['date_graduated'])->format('F j, Y') : 'Not yet graduated'); ?>

            </td>
        </tr>
    </table>

    <?php
        // Group the stored grade rows by term so the transcript reads chronologically.
        $terms = collect($payload['grades'] ?? [])->groupBy(
            fn ($row) => trim(($row['academic_year'] ?? 'Unspecified') . ' · ' . ($row['semester'] ?? ''))
        );
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($terms->isEmpty()): ?>
        <p style="text-align:center; color:#8a94ad; padding:24px 0">
            No academic records are on file for this student.
        </p>
    <?php else: ?>
        <table class="grades">
            <thead>
                <tr>
                    <th align="left" width="16%">Course code</th>
                    <th align="left">Descriptive title</th>
                    <th width="10%">Units</th>
                    <th width="10%">Grade</th>
                    <th width="14%">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="term">
                        <td colspan="5"><?php echo e($term); ?></td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($row['code'] ?? ''); ?></td>
                            <td><?php echo e($row['title'] ?? ''); ?></td>
                            <td class="num"><?php echo e(number_format((float) ($row['units'] ?? 0), 1)); ?></td>
                            <td class="num"><?php echo e($row['grade'] ?? ''); ?></td>
                            <td class="num"><?php echo e($row['remarks'] ?? 'Passed'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <tr>
                    <td colspan="2" align="right" style="font-weight:bold">Total units earned</td>
                    <td class="num" style="font-weight:bold"><?php echo e(number_format((float) ($payload['total_units'] ?? 0), 1)); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($payload['gwa'])): ?>
            <p style="margin-top:10px">
                <strong>General weighted average:</strong> <?php echo e(number_format((float) $payload['gwa'], 3)); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($payload['latin_honor'])): ?>
                    &nbsp;·&nbsp; <strong>Honor:</strong> <?php echo e($payload['latin_honor']); ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <p style="margin-top:14px; font-size:8pt; color:#5b6784">
        <strong>Grading system.</strong> 1.00 is the highest passing grade and 3.00 the lowest;
        5.00 is failure. INC means incomplete, DRP means officially dropped.
        This transcript covers all work completed at <?php echo e($payload['institution']); ?> as of the date of issue.
    </p>

    <table class="sig-block" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%">
                <div class="sig-line">
                    <div class="sig-name"><?php echo e(config('celeste.officials.records_officer')); ?></div>
                    <div class="sig-role">Records Officer</div>
                </div>
            </td>
            <td width="50%">
                <div class="sig-line">
                    <div class="sig-name"><?php echo e(config('celeste.officials.registrar')); ?></div>
                    <div class="sig-role">University Registrar</div>
                </div>
            </td>
        </tr>
    </table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf._shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/pdf/transcript-of-records.blade.php ENDPATH**/ ?>