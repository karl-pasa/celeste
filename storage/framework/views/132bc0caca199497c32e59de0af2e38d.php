
<?php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    $isNew = ($p['admission_type'] ?? 'new') === 'new';

    // Literal characters, not HTML entities: Blade escapes {{ }} output, so an
    // entity would print as its own source text.
    $tickNew = $isNew ? '×' : ' ';
    $tickTr  = $isNew ? ' ' : '×';

    $subjects = collect($p['grades'] ?? []);
    $grouped  = $subjects->groupBy(fn ($r) => trim(
        ($r['term'] ?? '') ?: (($r['semester'] ?? '') . ' ' . ($r['academic_year'] ?? ''))
    ));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo e($certificate->serial_number); ?></title>
<style>
    @page { size: 216mm 330mm; margin: 7mm 7mm 5mm 7mm; }

    /* DejaVu ships with Dompdf and carries ñ, which the core fonts do not. */
    body { font-family: "DejaVu Sans", sans-serif; font-size: 6.8pt; color:#000; margin:0; }

    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; }

    .bx { border: .7pt solid #000; }
    .bx > tbody > tr > td { padding: .7mm 1.4mm; }

    /* The blue rule runs behind the heading only, not the full column. */
    .bar { background:#9DC3E6; font-size:7pt; padding:.5mm 1.4mm; display:block; }

    .h-rep  { font-family:"Times New Roman",serif; font-size:8pt;  color:#1F4E79; }
    .h-uni  { font-family:"Times New Roman",serif; font-size:14pt; color:#1F4E79; letter-spacing:.3pt; }
    .h-camp { font-family:"Times New Roman",serif; font-size:8pt;  color:#1F4E79; }
    .h-off  { font-family:"Times New Roman",serif; font-size:10pt; }
    .h-tel  { font-size:5.8pt; }
    .h-ttl  { font-family:"Times New Roman",serif; font-size:12pt; color:#1F4E79;
              background:#9DC3E6; text-align:center; padding:.6mm 0; letter-spacing:.4pt; }
    .formno { font-size:6pt; text-align:right; }

    .lbl   { font-size:6.8pt; }
    .plain { font-size:6.8pt; }
    .val   { font-size:6.8pt; border-bottom:.5pt solid #000; }

    .tick { border:.7pt solid #000; padding:0 1.3mm; }

    .photo { border:.7pt solid #000; width:24mm; height:24mm;
             text-align:center; font-size:6pt; color:#777; }

    /* Page two: column labels above open space, no surrounding box. */
    .subj-head td { font-size:6.8pt; padding:.8mm .6mm; border:0; }
    .subj td { font-size:6.8pt; padding:.35mm 1.2mm; height:4.2mm; border:0; }
    .subj .close { text-align:center; padding:2mm 0; }

    /* The subject area is given a fixed height so the footer lands in the same
       position regardless of how many subjects there are. Without it, a short
       transcript pulls the signature block and the page rule up the sheet, and
       two copies of the same document no longer line up. */
    .subj-area { height: 165mm; vertical-align: top; }

    .lg   { font-size:5.9pt; }
    .lg td { padding:.5mm 1.2mm; }
    .note { font-size:5.9pt; }
    .sig-line { border-bottom:.5pt solid #000; width:42mm; }
    .reg-name { font-size:7.4pt; text-align:center; }
    .reg-role { font-size:6.8pt; text-align:center; }
    .ft td { font-size:6pt; padding:.6mm 1.2mm; }

    .mono { font-family:"DejaVu Sans Mono", monospace; }
    .brk  { page-break-after: always; }
</style>
</head>
<body>


<?php echo $__env->make('pdf.partials.tor-header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('pdf.partials.tor-identity', ['p' => $p], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<table class="bx" style="margin-top:2mm">
    <tr><td colspan="2" style="padding:0"><span class="bar">PERSONAL INFORMATION</span></td></tr>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
        'Address'     => $v('address'),
        'Gender'      => $v('gender'),
        'Nationality' => $v('nationality'),
        'Birthdate'   => $d('birth_date'),
        'Birthplace'  => $v('birthplace'),
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td class="lbl" style="width:26mm"><?php echo e($label); ?>:</td>
            <td class="plain"><?php echo e($value); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</table>


<table class="bx" style="margin-top:2mm">
    <tr>
        
        <td style="width:52%; border-right:.7pt solid #000; padding:0">
            <table>
                <tr><td colspan="2" style="padding:0"><span class="bar">ADMISSION DATA</span></td></tr>

                <tr>
                    <td colspan="2" class="lbl" style="padding:1mm 1.4mm .3mm">
                        A. <span class="tick"><?php echo e($tickNew); ?></span> NEW
                    </td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                    'School'         => $isNew ? $v('adm_new_school') : '',
                    'Address'        => $isNew ? $v('adm_new_address') : '',
                    'Course'         => $isNew ? $v('adm_new_course') : '',
                    'Year Graduated' => $isNew ? $v('adm_new_year_graduated') : '',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="lbl" style="width:26mm; padding:.5mm 1.4mm .5mm 5mm"><?php echo e($label); ?>:</td>
                        <td class="plain" style="padding:.5mm 1.4mm"><?php echo e($value); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <tr>
                    <td colspan="2" class="lbl" style="padding:2.5mm 1.4mm .3mm 5mm">
                        <span class="tick"><?php echo e($tickTr); ?></span> TRANSFEREE
                    </td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                    'School'               => $isNew ? '' : $v('adm_tr_school'),
                    'Address'              => $isNew ? '' : $v('adm_tr_address'),
                    'Course'               => $isNew ? '' : $v('adm_tr_course'),
                    'Year Graduated'       => $isNew ? '' : $v('adm_tr_year_graduated'),
                    'Admission Credential' => $isNew ? '' : $v('adm_tr_credential'),
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="lbl" style="width:26mm; padding:.5mm 1.4mm .5mm 5mm"><?php echo e($label); ?>:</td>
                        <td class="plain" style="padding:.5mm 1.4mm"><?php echo e($value); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <tr>
                    <td class="lbl" style="padding:2.5mm 1.4mm 1.5mm">B.&nbsp; Date of Admission:</td>
                    <td class="plain" style="padding:2.5mm 1.4mm 1.5mm"><?php echo e($d('date_admitted')); ?></td>
                </tr>
            </table>
        </td>

        
        <td style="width:48%; padding:0">
            <table>
                <tr>
                    <td style="padding:0">
                        <table>
                            <tr><td colspan="2" style="padding:0"><span class="bar">GRADUATION DATA</span></td></tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                                'Date Conferred'       => $d('date_conferred'),
                                'Board Resolution No.' => $v('board_resolution_no'),
                                'Date'                 => $d('board_resolution_date'),
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="lbl" style="width:30mm; padding:.5mm 1.4mm .5mm 5mm"><?php echo e($label); ?>:</td>
                                    <td class="plain" style="padding:.5mm 1.4mm"><?php echo e($value); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <tr><td colspan="2" style="height:6mm"></td></tr>
                            <tr>
                                <td class="lbl" style="padding:.5mm 1.4mm 2mm 5mm">Awards:</td>
                                <td class="plain" style="padding:.5mm 1.4mm 2mm"><?php echo e($v('awards')); ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:.7pt solid #000; padding:0">
                        <span class="bar">NSTP SERIAL NO.:</span>
                        <div class="plain" style="text-align:center; padding:6mm 1.4mm 5mm">
                            <?php echo e($v('nstp_serial_no')); ?>

                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


<table class="bx" style="margin-top:2mm">
    <tr><td class="lbl">PROGRAM ACCREDITATION STATUS:</td></tr>
    <tr><td style="height:8mm"><?php echo e($v('program_accreditation')); ?></td></tr>
</table>


<table style="margin-top:2mm">
    <tr>
        <td style="width:72%; padding-right:2.5mm">
            <table class="bx">
                <tr><td class="lbl">GRANTED TRANSFER CREDENTIALS :</td></tr>
                <tr><td style="height:9mm"><?php echo e($v('granted_transfer_credentials')); ?></td></tr>
            </table>
        </td>
        <td style="width:28%">
            <table class="bx">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                    'OR NO.' => $v('or_no'),
                    'DATE'   => $d('or_date'),
                    'AMOUNT' => filled($p['cert_fee'] ?? $p['or_amount'] ?? null)
                                ? 'Php ' . ($p['cert_fee'] ?? $p['or_amount']) : '',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="lbl" style="width:15mm"><?php echo e($label); ?>:</td>
                        <td class="plain"><?php echo e($value); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>
        </td>
    </tr>
</table>


<table class="bx" style="margin-top:2mm">
    <tr><td class="lbl">REMARKS:</td></tr>
    <tr><td style="height:11mm"><?php echo e($v('remarks')); ?></td></tr>
</table>

<?php echo $__env->make('pdf.partials.tor-footer', [
    'p' => $p, 'page' => 1, 'pages' => 2,
    'certificate' => $certificate, 'qr' => $qr ?? null,
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="brk"></div>


<?php echo $__env->make('pdf.partials.tor-header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('pdf.partials.tor-identity', ['p' => $p], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<table class="subj-head" style="margin-top:3mm">
    <tr>
        <td style="width:22%">Subject Code</td>
        <td style="width:46%">Descriptive Title of Subject</td>
        <td style="width:11%; text-align:center">Final<br>Rating</td>
        <td style="width:11%; text-align:center">Removal<br>Rating</td>
        <td style="width:10%; text-align:center">Credits</td>
    </tr>
</table>

<table>
<tr><td class="subj-area">
<table class="subj">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term => $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($term)): ?>
            <tr><td colspan="5"><?php echo e(mb_strtoupper($term)); ?></td></tr>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td style="width:22%"><?php echo e($s['code'] ?? ''); ?></td>
                <td style="width:46%"><?php echo e($s['title'] ?? ''); ?></td>
                <td style="width:11%; text-align:center"><?php echo e($s['grade'] ?? ''); ?></td>
                <td style="width:11%; text-align:center"><?php echo e($s['removal'] ?? ''); ?></td>
                <td style="width:10%; text-align:center"><?php echo e(filled($s['units'] ?? null) ? number_format((float) $s['units'], 1) : ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <tr>
        <td colspan="5" class="close">
            psupsupsupsupsupsupsupsupsupsupsupsu&nbsp;&nbsp;transcript closed&nbsp;&nbsp;psupsupsupsupsupsupsupsupsupsupsupsu
        </td>
    </tr>
</table>
</td></tr>
</table>

<?php echo $__env->make('pdf.partials.tor-footer', [
    'p' => $p, 'page' => 2, 'pages' => 2,
    'certificate' => $certificate, 'qrPath' => $qrPath ?? null,
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>
</html>
<?php /**PATH C:\laragon\www\celeste\resources\views/pdf/transcript-of-records.blade.php ENDPATH**/ ?>