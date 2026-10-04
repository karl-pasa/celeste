
<table>
    <tr>
        <td style="width:13mm; vertical-align:middle">
            <?php $seal = public_path('images/psu-seal.png'); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_readable($seal)): ?>
                <img src="<?php echo e($seal); ?>" style="width:12mm; height:12mm">
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </td>
        <td style="text-align:center; vertical-align:middle">
            <div class="h-rep">Republic of the Philippines</div>
            <div class="h-uni"><?php echo e(mb_strtoupper(config('celeste.institution.name', 'PARTIDO STATE UNIVERSITY'))); ?></div>
            <div class="h-camp"><?php echo e(config('celeste.institution.campus', 'Camarines Sur')); ?></div>
        </td>
    </tr>
</table>

<div class="rule"></div>
<div class="formno"><?php echo e($formNo); ?></div>
<?php /**PATH C:\laragon\www\celeste\resources\views/pdf/partials/tc-header.blade.php ENDPATH**/ ?>