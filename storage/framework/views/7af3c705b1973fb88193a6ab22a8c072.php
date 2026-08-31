
<table class="bx lg" style="margin-top:2mm">
    <tr>
        <td style="width:19mm">Rating System:</td>
        <td>
            1.0-99&nbsp;&nbsp;&nbsp;1.1-98&nbsp;&nbsp;&nbsp;1.2-97&nbsp;&nbsp;&nbsp;1.25-96&nbsp;&nbsp;&nbsp;1.3-95&nbsp;&nbsp;&nbsp;1.4-94&nbsp;&nbsp;&nbsp;1.5-93&nbsp;&nbsp;&nbsp;1.6-92&nbsp;&nbsp;&nbsp;1.7-91<br>
            1.75-90&nbsp;&nbsp;1.8-89&nbsp;&nbsp;&nbsp;1.9-88&nbsp;&nbsp;&nbsp;2.0-87&nbsp;&nbsp;&nbsp;&nbsp;2.1-86&nbsp;&nbsp;&nbsp;2.2-85&nbsp;&nbsp;&nbsp;2.25-84&nbsp;&nbsp;2.3-83&nbsp;&nbsp;&nbsp;2.4-82<br>
            2.5-81&nbsp;&nbsp;&nbsp;2.6-80&nbsp;&nbsp;&nbsp;2.7-79&nbsp;&nbsp;&nbsp;2.75-78&nbsp;&nbsp;&nbsp;2.8-77&nbsp;&nbsp;&nbsp;2.9-76&nbsp;&nbsp;&nbsp;3.0-75&nbsp;&nbsp;&nbsp;&nbsp;5.0-Failed
        </td>
    </tr>
    <tr>
        <td style="width:19mm; font-style:italic">Official Rating Marks:</td>
        <td>
            OD-Officially Dropped&nbsp;&nbsp;&nbsp;UD-Unofficially Dropped&nbsp;&nbsp;&nbsp;INC-Incomplete&nbsp;&nbsp;&nbsp;NC-No Credit&nbsp;&nbsp;&nbsp;IP-In Progress (For SGS only)
        </td>
    </tr>
    <tr>
        <td colspan="2" class="note">
            Note:&nbsp; This transcript is valid only when it bears the seal of the University and the
            original signature in ink of the Registrar. Any erasure or alteration made on this copy
            renders the whole transcript invalid.
        </td>
    </tr>
</table>

<table class="bx" style="border-top:0">
    <tr>
        <td style="width:34%; border-right:.7pt solid #000; padding:0">
            <table>
                <tr><td class="lbl" style="padding:.7mm 1.4mm">Prepared by:</td></tr>
                <tr><td style="padding:0 1.4mm 1mm">
                    <div style="height:6mm"></div>
                    <div class="sig-line"></div>
                    <div style="font-size:6pt"><?php echo e($p['prepared_by'] ?? ''); ?></div>
                </td></tr>
                <tr><td class="lbl" style="border-top:.7pt solid #000; padding:.7mm 1.4mm">Reviewed by:</td></tr>
                <tr><td style="padding:0 1.4mm 1mm">
                    <div style="height:6mm"></div>
                    <div class="sig-line"></div>
                    <div style="font-size:6pt"><?php echo e($p['reviewed_by'] ?? ''); ?></div>
                </td></tr>
            </table>
        </td>

        <td style="width:36%; border-right:.7pt solid #000; vertical-align:bottom; padding-bottom:2mm">
            <div style="height:13mm"></div>
            <div class="reg-name"><?php echo e(config('celeste.officials.registrar', 'JOJI B. MIRAÑA, EdD')); ?></div>
            <div class="reg-role">University Registrar</div>
        </td>

        
        <td style="width:30%; text-align:center; padding:1.5mm 1mm">
            <div style="font-size:7pt">Not valid without school seal</div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($qr)): ?>
                <img src="<?php echo e($qr); ?>" style="width:22mm; height:22mm; margin-top:1.2mm">
            <?php else: ?>
                <div style="width:22mm; height:22mm; margin:1.2mm auto 0;
                            border:.5pt dashed #999; font-size:5pt; color:#999">
                    <div style="padding-top:9mm">QR</div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="mono" style="font-size:6.2pt; margin-top:.6mm"><?php echo e($certificate->serial_number); ?></div>
            <div style="font-size:5.4pt; color:#555">Scan to verify</div>

            <div style="font-size:6.4pt; margin-top:1.2mm">
                Page <span style="border-bottom:.5pt solid #000; padding:0 2.5mm"><?php echo e($page); ?></span>
                of <span style="border-bottom:.5pt solid #000; padding:0 2.5mm"><?php echo e($pages); ?></span>
            </div>
        </td>
    </tr>
</table>

<table class="bx ft" style="border-top:0">
    <tr>
        <td style="width:34%; border-right:.7pt solid #000">Effectivity Date: January 2, 2025</td>
        <td style="width:36%; border-right:.7pt solid #000; text-align:center">Rev. No: 02</td>
        <td style="width:30%"></td>
    </tr>
</table>
<?php /**PATH C:\laragon\www\celeste\resources\views/pdf/partials/tor-footer.blade.php ENDPATH**/ ?>