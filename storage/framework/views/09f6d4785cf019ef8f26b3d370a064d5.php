<?php $__env->startSection('document'); ?>
    <div class="doc-title">CERTIFICATE OF ENROLMENT</div>
    <div class="doc-sub"><?php echo e($payload['semester'] ?? ''); ?> <?php echo e($payload['academic_year'] ? ', A.Y. ' . $payload['academic_year'] : ''); ?></div>

    <p>TO WHOM IT MAY CONCERN:</p>

    <p class="indent">
        This is to certify that <span class="name-inline"><?php echo e($payload['full_name']); ?></span>,
        bearing Student Number <?php echo e($payload['student_number']); ?>, is officially enrolled at
        <?php echo e($payload['institution']); ?>, <?php echo e($payload['campus']); ?>, for the period stated below.
    </p>

    <table class="fields">
        <tr><td class="label">Program</td><td class="value"><?php echo e($payload['program']); ?></td></tr>
        <tr><td class="label">College</td><td class="value"><?php echo e($payload['college']); ?></td></tr>
        <tr><td class="label">Year level</td><td class="value"><?php echo e($payload['year_level'] ?? '—'); ?></td></tr>
        <tr><td class="label">Semester</td><td class="value"><?php echo e($payload['semester'] ?? '—'); ?></td></tr>
        <tr><td class="label">Academic year</td><td class="value"><?php echo e($payload['academic_year'] ?? '—'); ?></td></tr>
        <tr><td class="label">Enrolment status</td><td class="value" style="text-transform:capitalize"><?php echo e($payload['status'] ?? 'Enrolled'); ?></td></tr>
    </table>

    <p class="indent">
        This certification is issued upon the request of the above-named student
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($payload['purpose'])): ?>
            for the purpose of <?php echo e($payload['purpose']); ?>.
        <?php else: ?>
            for whatever legal purpose it may serve.
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </p>

    <p class="indent">
        Issued this <?php echo e(\Illuminate\Support\Carbon::parse($payload['issued_on'])->format('jS \d\a\y \o\f F, Y')); ?>

        at <?php echo e($payload['campus']); ?>, Philippines.
    </p>

    <table class="sig-block" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                <div class="sig-line">
                    <div class="sig-name"><?php echo e(config('celeste.officials.registrar')); ?></div>
                    <div class="sig-role">University Registrar</div>
                </div>
            </td>
        </tr>
    </table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('pdf._shell', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/pdf/certificate-of-enrolment.blade.php ENDPATH**/ ?>