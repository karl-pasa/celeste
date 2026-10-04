<?php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    // The form reads "a ___ year student | graduate of". Where the record says
    // which applies, only that word prints; otherwise both print as on the
    // blank form, for the office to strike one through.
    $standingText = match ($p['standing'] ?? null) {
        'graduate' => 'year graduate of',
        'student'  => 'year student of',
        default    => 'year student | graduate of',
    };

    $registrar = config('celeste.officials.registrar', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo e($certificate->serial_number); ?></title>
<style>
    /*
      | 4mm page margin leaves 202mm of printable height on a 210mm sheet, and
      | the sheet below claims 200mm of it. height on a table is a minimum in
      | CSS, not a cap -- it grows if the content demands it, and a grown row
      | paginates.
      |
      | The return slip is the taller of the two columns: it carries six ruled
      | entries in its receipt block where the credential carries three. Its
      | spacing is therefore set tighter than the credential's, which is why
      | the two sides do not share margin values.
    */
    @page { size: 297mm 210mm; margin: 4mm; }

    /* DejaVu ships with Dompdf and carries ñ, which the core fonts do not. */
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color:#000; margin:0; }

    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: bottom; }

    /* table-layout:fixed holds the two columns at a true 50/50. Without it
       Dompdf sizes them by content, and a long programme name widens the
       credential side at the return slip's expense. */
    .sheet { border: .8pt solid #000; height: 200mm; table-layout: fixed; }
    .sheet > tbody > tr > td { vertical-align: top; }

    /* The cut line. The printed form marks it with scissors; a dashed rule
       reads the same and survives photocopying better than a glyph. */
    .cut  { border-right: .8pt dashed #000; }
    .half { padding: 3mm 4.5mm; }

    .h-rep  { font-family:"Times New Roman",serif; font-size:8pt; }
    .h-uni  { font-family:"Times New Roman",serif; font-size:12.5pt; color:#1F4E79; letter-spacing:.2pt; }
    .h-camp { font-family:"Times New Roman",serif; font-size:8.5pt; }
    .formno { font-size:7pt; text-align:right; }
    .rule   { border-bottom:.8pt solid #1F4E79; margin-top:1mm; }

    .office { font-family:"Times New Roman",serif; font-size:11pt; text-align:center;
              letter-spacing:.6pt; margin-top:4mm; }
    .title  { font-family:"Times New Roman",serif; font-size:15pt; text-align:center;
              letter-spacing:1pt; margin-top:6mm; }

    /*
      | The body of the credential is set in a script face on the printed form.
      | Dompdf carries no script font, so italic serif stands in -- it keeps the
      | contrast against the upright entries without needing an embedded font.
    */
    .lead  { font-family:"Times New Roman",serif; font-style:italic; font-size:9.5pt; }
    .plain { font-family:"Times New Roman",serif; font-style:normal; font-size:9.5pt; }

    /*
      | One rule for every filled value: same family, same size, same colour,
      | so the form does not read as though three people completed it.
      | Applied to table cells rather than inline spans, because Dompdf honours
      | a width on a <td> and largely ignores one on an inline-block.
    */
    .val {
        border-bottom: .7pt solid #000;
        font-family: "Times New Roman", serif;
        font-style: normal;
        font-size: 9.5pt;
        color: #000;
        text-align: center;
        padding: 0 1mm .3mm;
    }

    /* Rows of the certifying sentence. */
    .sentence td { font-family:"Times New Roman",serif; font-style:italic; font-size:9.5pt;
                   padding-bottom:.3mm; }
    .sentence .val { font-style:normal; }

    .sig-line { border-bottom:.7pt solid #000; }
    .sig-cap  { font-size:8pt; padding-top:.8mm; }
    .sig-name { font-family:"Times New Roman",serif; font-size:9.5pt; text-align:center;
                padding-bottom:.4mm; }

    .sealbox { border:.7pt dashed #000; text-align:center;
               font-size:7pt; line-height:1.45; padding:1.6mm 1mm; }

    .receipt td   { font-size:8pt; padding:.35mm 0; }
    .receipt .val { font-family:"DejaVu Sans",sans-serif; font-size:8pt;
                    text-align:left; padding-left:1mm; }

    .foot td { font-size:7.5pt; }
    .mono { font-family:"DejaVu Sans Mono", monospace; }

    .rs-title { font-size:9pt; }
    .rs-sub   { font-size:6.5pt; }
    .rs-line  { border-bottom:.7pt solid #000; height:4mm; }
    .rs-cap   { font-size:7.5pt; text-align:center; }
    .tick     { border:.7pt solid #000; width:3mm; height:3mm; }
</style>
</head>
<body>

<table class="sheet">
<tr>

    
    <td class="half cut" style="width:50%">

        <?php echo $__env->make('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="office">OFFICE OF THE REGISTRAR</div>
        <div class="title">TRANSFER CREDENTIAL</div>

        
        <table style="margin-top:6mm">
            <tr>
                <td style="width:46%"></td>
                <td class="val" style="width:34%"><?php echo e($d('issued_on')); ?></td>
                <td style="width:20%"></td>
            </tr>
        </table>

        <div style="margin-top:6mm; font-size:9pt">To Whom It May Concern:</div>

        
        <table class="sentence" style="margin-top:3.5mm">
            <tr>
                <td style="width:5mm"></td>
                <td style="width:42mm">This is to certify that <span class="plain">MR. / MS.</span></td>
                <td class="val"><?php echo e($v('full_name')); ?></td>
                <td style="width:5mm; text-align:center">of</td>
                <td class="val" style="width:40mm"><?php echo e($v('address')); ?></td>
                <td style="width:2mm">,</td>
            </tr>
        </table>

        <table class="sentence" style="margin-top:2.5mm">
            <tr>
                <td style="width:4mm">a</td>
                <td class="val" style="width:14mm"><?php echo e($v('year_level')); ?></td>
                <td style="width:31mm; text-align:center"><?php echo e($standingText); ?></td>
                <td class="val"><?php echo e($v('program')); ?></td>
                <td style="width:45mm; font-size:9pt; padding-left:1.5mm">and whose signature appears below</td>
            </tr>
        </table>

        <div class="lead" style="margin-top:1.5mm">
            has been granted Transfer Credential effective today.
        </div>

        <div class="lead" style="margin-top:4mm">
            <span class="plain">His/Her</span> Transcript of Records will be forwarded only upon
            receipt of the return slip.
        </div>

        
        <table style="margin-top:1mm">
            <tr><td class="sig-name" style="width:64mm"><?php echo e($v('full_name')); ?></td><td></td></tr>
            <tr><td class="sig-line"></td><td></td></tr>
            <tr><td class="sig-cap">Signature of Student over Printed Name</td><td></td></tr>
        </table>

        
        <table style="margin-top:7mm">
            <tr>
                <td style="width:38%"></td>
                <td class="sig-line"></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-family:'Times New Roman',serif; font-size:9.5pt;
                           text-align:center; padding-top:.6mm"><?php echo e($registrar); ?></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-size:7.5pt; text-align:center">University Registrar</td>
            </tr>
        </table>

        
        <table style="margin-top:6mm">
            <tr>
                <td style="width:38%; vertical-align:bottom">
                    <table class="receipt">
                        <tr>
                            <td style="width:16mm">OR:</td>
                            <td class="val"><?php echo e($v('or_no')); ?></td>
                        </tr>
                        <tr>
                            <td>Date:</td>
                            <td class="val"><?php echo e($d('or_date')); ?></td>
                        </tr>
                        <tr>
                            <td>Cert. Fee:</td>
                            <td class="val">Php <?php echo e($v('cert_fee')); ?></td>
                        </tr>
                    </table>
                </td>

                <td style="width:40%; vertical-align:bottom; text-align:center">
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($qr)): ?>
                        <img src="<?php echo e($qr); ?>" style="width:19mm;height:19mm">
                    <?php else: ?>
                        <div style="width:19mm;height:19mm;border:.5pt dashed #999;
                                    font-size:5pt;color:#999;margin:0 auto">
                            <div style="padding-top:7mm">QR</div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="mono" style="font-size:6.2pt; padding-top:.6mm">
                        <?php echo e($certificate->serial_number); ?>

                    </div>
                    <div style="font-size:5.6pt; color:#444">Scan to verify</div>
                </td>

                
                <td style="width:22%"></td>
            </tr>
        </table>

    </td>

    
    <td class="half" style="width:50%">

        <?php echo $__env->make('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23-A'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div style="margin-top:3mm">
            <div class="rs-title">Return Slip</div>
            <div class="rs-sub">(to be filled by requesting school)</div>
        </div>

        
        <table style="margin-top:5mm">
            <tr>
                <td style="width:14%"></td>
                <td class="rs-line"></td>
                <td style="width:6%"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Name of School</td>
                <td></td>
            </tr>
            <tr><td colspan="3" style="height:2mm"></td></tr>
            <tr>
                <td></td>
                <td class="rs-line"></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Address</td>
                <td></td>
            </tr>
        </table>

        <table style="margin-top:3mm">
            <tr>
                <td style="width:50%"></td>
                <td class="rs-line"></td>
                <td style="width:6%"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Date</td>
                <td></td>
            </tr>
        </table>

        <div style="margin-top:4.5mm; font-size:8.5pt; line-height:1.35">
            <div>The Registrar</div>
            <div><?php echo e(config('celeste.institution.name', 'Partido State University')); ?></div>
            <div>Goa, <?php echo e(config('celeste.institution.campus', 'Camarines Sur')); ?></div>
        </div>

        <div style="margin-top:3.5mm; font-size:8.5pt">Madam:</div>

        <table style="margin-top:1.5mm">
            <tr>
                <td style="font-size:8.5pt; line-height:1.45" colspan="3">
                    This is to acknowledge receipt of the Transfer Credential granted
                </td>
            </tr>
            <tr>
                <td style="width:20mm; font-size:8.5pt">to Mr. /Ms.</td>
                <td class="rs-line"></td>
                <td style="width:2mm; font-size:8.5pt">.</td>
            </tr>
        </table>

        <table style="margin-top:6mm">
            <tr>
                <td style="width:28%"></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Signature over Printed Name</td>
            </tr>
            <tr><td colspan="2" style="height:3mm"></td></tr>
            <tr>
                <td></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Position/Designation</td>
            </tr>
        </table>

        
        <table style="margin-top:3mm">
            <tr>
                <td style="width:58%; vertical-align:top">
                    <table class="receipt">
                        <tr><td style="width:22mm">OR No.:</td><td class="val"></td></tr>
                        <tr><td>Date:</td><td class="val"></td></tr>
                        <tr><td>Cert. Fee:</td><td class="val">Php</td></tr>
                        <tr><td>T.C.</td><td class="val"></td></tr>
                        <tr><td>Course:</td><td class="val"></td></tr>
                        <tr><td>Year Graduated:</td><td class="val"></td></tr>
                    </table>
                </td>
                <td style="width:42%; vertical-align:bottom">
                    <table>
                        <tr>
                            <td class="tick"></td>
                            <td style="font-size:7.5pt; font-style:italic; padding-left:1.5mm">
                                Please entrust to the bearer.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>

</tr>
</table>


<div style="position:absolute; left:123.5mm; top:163mm; width:42mm; background:#fff">
    <div class="sealbox">Documentary<br>Stamp<br>And Dry Seal Here</div>
</div>

</body>
</html><?php /**PATH C:\laragon\www\celeste\resources\views/pdf/honorable-dismissal.blade.php ENDPATH**/ ?>