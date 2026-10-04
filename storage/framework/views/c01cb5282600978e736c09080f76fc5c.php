
<?php $val = fn (string $k) => filled($p[$k] ?? null) ? $p[$k] : ''; ?>
<table style="margin-top:1.2mm">
    <tr>
        <td style="width:75%">
            <table>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                    'Student Number'       => $val('student_number'),
                    'Name'                 => $val('full_name'),
                    'Course'               => $val('program'),
                    'Major/Specialization' => $val('major'),
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="plain" style="width:32mm; padding:.7mm 0"><?php echo e($label); ?>:</td>
                        <td class="plain" style="padding:.7mm 1.2mm"><?php echo e($value); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>
        </td>
        <td style="width:25%; padding-left:3mm">
            <div class="photo"><div style="padding-top:9mm">2 x 2<br>PHOTO</div></div>
        </td>
    </tr>
</table>
<?php /**PATH C:\laragon\www\celeste\resources\views/pdf/partials/tor-identity.blade.php ENDPATH**/ ?>