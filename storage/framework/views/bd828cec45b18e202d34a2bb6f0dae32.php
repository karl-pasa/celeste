<?php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  NAME CASE
      | ─────────────────────────────────────────────────────────────────────
      |  The payload stores the name uppercase, and the hash is taken over
      |  the payload, so it must not be touched. The diploma on file is set
      |  in title case -- "Lypel Joy Armea" -- and blackletter in all caps is
      |  close to unreadable, so the case is changed here, at render time
      |  only. The stored value and the hash are unaffected.
      |
      |  mb_convert_case alone would turn "DELA CRUZ" into "Dela Cruz", which
      |  is right, but also "JOSE III" into "Jose Iii". The particles and
      |  suffixes below are corrected afterwards.
    */
    $titleCase = function (string $name): string {
        $out = mb_convert_case(mb_strtolower(trim($name)), MB_CASE_TITLE, 'UTF-8');

        $fixes = [
            '/\bIi\b/u'   => 'II',   '/\bIii\b/u' => 'III',
            '/\bIv\b/u'   => 'IV',   '/\bVi\b/u'  => 'VI',
            '/\bJr\b/u'   => 'Jr',   '/\bSr\b/u'  => 'Sr',
            '/\bDela\b/u' => 'Dela', '/\bDe\b/u'  => 'De',
            '/\bDel\b/u'  => 'Del',  '/\bSan\b/u' => 'San',
            '/\bSanta\b/u'=> 'Santa','/\bSto\b/u' => 'Sto',
        ];

        return preg_replace(array_keys($fixes), array_values($fixes), $out);
    };

    $fullName = $titleCase($v('full_name'));
    $degree   = $v('program');
    $honor    = $v('latin_honor');

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  FITTING LONG TEXT
      | ─────────────────────────────────────────────────────────────────────
      |  The content column is 160mm wide. "Lypel Joy Armea" fits at full
      |  size; "Ma. Kristine Joy R. Buenaflor-Villanueva" does not, and
      |  "Bachelor of Science in Information Technology" is eleven characters
      |  longer than the degree on the photographed diploma.
      |
      |  Dompdf cannot measure text and resize to fit, so the size steps down
      |  by character count instead. The thresholds were chosen so that the
      |  longest real programme at Partido State University still sits on one
      |  line inside the frame.
    */
    $fit = function (string $text, array $steps, int $floor): int {
        $len = mb_strlen($text);
        foreach ($steps as $limit => $size) {
            if ($len <= $limit) return $size;
        }
        return $floor;
    };

    $nameSize   = $fit($fullName, [16 => 32, 22 => 28, 28 => 24, 36 => 20], 17);
    $degreeSize = $fit($degree,   [30 => 20, 40 => 18, 50 => 16, 60 => 14], 12);

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  CONFERRAL DATE
      | ─────────────────────────────────────────────────────────────────────
      |  Printed as three pieces -- "this 8th day of June in the year of our
      |  Lord, 2026" -- with the ordinal suffix raised, as on the original.
      |
      |  A record with no graduation date falls back to the issue date rather
      |  than printing "this day of in the year of our Lord, ." A diploma
      |  with gaps in that sentence is worse than one carrying a date the
      |  registrar can correct.
    */
    $conferred = null;
    foreach (['date_graduated', 'issued_on'] as $key) {
        if (filled($p[$key] ?? null)) {
            try { $conferred = \Illuminate\Support\Carbon::parse($p[$key]); break; }
            catch (\Throwable) { }
        }
    }
    $conferred ??= $certificate->issued_on ?? now();

    $dayNumber = $conferred->format('j');
    $daySuffix = substr($conferred->format('jS'), strlen($dayNumber));
    $monthName = $conferred->format('F');
    $yearName  = $conferred->format('Y');

    $resolutionNo   = $v('board_resolution');
    $resolutionDate = null;
    if (filled($p['board_resolution_date'] ?? null)) {
        try { $resolutionDate = \Illuminate\Support\Carbon::parse($p['board_resolution_date'])->format('F j, Y'); }
        catch (\Throwable) { $resolutionDate = (string) $p['board_resolution_date']; }
    }

    $registrar  = config('celeste.officials.registrar', '');
    $registrarT = config('celeste.officials.registrar_title', 'University Registrar');
    $president  = config('celeste.officials.president', '');
    $presidentT = config('celeste.officials.president_title', 'SUC President III');

    // A missing asset leaves a gap rather than throwing, so the office can
    // test the layout before every scan has been collected.
    $asset = function (string $relative) {
        $path = public_path($relative);
        return is_file($path) ? $path : null;
    };

    $border     = $asset('images/diploma-border.png');
    $seal       = $asset('images/psu-seal.png');
    $signRegist = $asset('images/sig-registrar.png');
    $signPres   = $asset('images/sig-president.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo e($certificate->serial_number); ?></title>
<style>
    /*
      | ─────────────────────────────────────────────────────────────────────
      |  GEOMETRY
      | ─────────────────────────────────────────────────────────────────────
      |  Taken off the photographed diploma by proportion and converted to
      |  millimetres on A4 portrait. The photograph is 1079 x 1512 px, a
      |  ratio of 0.714, against A4's 0.707 -- within the error of a
      |  hand-held camera, so A4 it is.
      |
      |    sheet                      210 x 297mm
      |    frame, outer edge          8mm in from every side
      |    frame, chevron band        4.3mm thick
      |    content column             left 25mm, width 160mm
      |
      |  Vertical positions are the TOP of each block, derived from the
      |  measured centre of the line less roughly half its height:
      |
      |    Republic of the Philippines      27.3mm
      |    Partido State University         35.0mm
      |    Camarines Sur                    47.4mm
      |    TO ALL PERSONS ...               59.3mm
      |    Greetings                        69.0mm
      |    BE IT KNOWN ... (2 lines)        88.0mm
      |    by authority ... (2 lines)       99.8mm
      |    conferee name                   116.8mm
      |    who has fulfilled ...           133.0mm
      |    degree                          143.0mm
      |    latin honour (optional)         152.5mm
      |    with all the rights ...         158.2mm
      |    IN TESTIMONY WHEREOF            175.4mm
      |    the seal of ... (2 lines)       182.1mm
      |    Given at Goa ... (2 lines)      199.4mm
      |    seal                            214.0mm
      |    signature strip                 228.0mm
      |    QR block                        255.0mm
      |    Board Resolution block          259.0mm
      |
      |  Everything is absolutely positioned. A diploma has no reflowing
      |  content, and positioning means a long programme name cannot push the
      |  signatures off the sheet the way flow layout would.
      |
      |  To nudge a line, change only its top value below. The content column
      |  is set once on .at -- do not give individual blocks their own left
      |  or width, or they will stop agreeing with each other.
    */
    @page { size: 210mm 297mm; margin: 0; }

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  FONTS
      | ─────────────────────────────────────────────────────────────────────
      |  Dompdf cannot use a face that is not registered here, and it falls
      |  back silently rather than warning. Both files go in public/fonts/
      |  under exactly these names.
      |
      |  storage/fonts/ must exist and be writable -- Dompdf writes its
      |  metrics cache there on the first render. A missing folder throws
      |  "Failed to open stream" on a .ufm file.
      |
      |  The signature block uses DejaVu Serif, not Times: the registrar's
      |  surname carries an ñ and the PDF core fonts do not have it.
    */
    @font-face {
        font-family: 'Blackletter';
        font-style: normal;
        font-weight: normal;
        src: url('<?php echo e(public_path("fonts/UnifrakturMaguntia-Regular.ttf")); ?>') format('truetype');
    }

    @font-face {
        font-family: 'Script';
        font-style: normal;
        font-weight: normal;
        src: url('<?php echo e(public_path("fonts/GreatVibes-Regular.ttf")); ?>') format('truetype');
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 210mm;
        height: 297mm;
        font-family: "DejaVu Serif", serif;
        color: #1c1c1c;
    }

    /*
      Every centred block shares this column. Constraining the width to
      160mm is what keeps a long name inside the frame -- at full sheet
      width the text would centre correctly but run over the border art.
    */
    .at {
        position: absolute;
        left: 25mm;
        width: 160mm;
        text-align: center;
    }

    .fraktur { font-family: 'Blackletter', serif; font-weight: normal; }
    .script  { font-family: 'Script', cursive; }
    .roman   { font-family: "Times New Roman", "DejaVu Serif", serif; }

    /* ── Frame ────────────────────────────────────────────────────────── */

    .border-art {
        position: absolute;
        top: 0; left: 0;
        width: 210mm;
        height: 297mm;
    }

    /* Shown only until the chevron artwork is in place. Same inset and
       thickness as the frame on the original, so the layout can be judged
       against the photo before the scan exists. */
    .border-rule {
        position: absolute;
        top: 8mm; left: 8mm;
        width: 189.4mm;
        height: 276.4mm;
        border: 4.3mm solid #3a3a3a;
    }

    .border-rule-inner {
        position: absolute;
        top: 13.5mm; left: 13.5mm;
        width: 182.2mm;
        height: 269.2mm;
        border: .35mm solid #3a3a3a;
    }

    /* ── Type ─────────────────────────────────────────────────────────── */

    .t-republic  { font-size: 15pt; }
    .t-uni       { font-size: 30pt; }
    .t-province  { font-size: 13pt; }
    .t-presents  { font-size: 13.5pt; }
    .t-greetings { font-size: 32pt; }
    .t-beitknown { font-size: 13pt;   line-height: 1.45; }
    .t-recommend { font-size: 14.5pt; line-height: 1.5; }
    .t-fulfilled { font-size: 14.5pt; }
    .t-honor     { font-size: 15pt; }
    .t-rights    { font-size: 14.5pt; line-height: 1.5; }
    .t-testimony { font-size: 13.5pt; }
    .t-seal-line { font-size: 14.5pt; line-height: 1.5; }
    .t-given     { font-size: 14.5pt; line-height: 1.55; }

    /* The original raises the ordinal suffix on the day of the month.
       vertical-align is one of the few inline properties Dompdf honours. */
    .ordinal { font-size: 9pt; vertical-align: super; }

    /* ── Seal ─────────────────────────────────────────────────────────── */

    /* The seal sits behind the signature strip and overlaps it slightly, as
       on the original where the strip is stamped across the seal's lower
       third. It is positioned separately rather than placed in the table so
       that overlap is possible at all -- a table cell cannot bleed. */
    .seal-wrap {
        position: absolute;
        top: 214mm;
        left: 25mm;
        width: 160mm;
        text-align: center;
    }

    .seal-wrap img { width: 46mm; height: 46mm; }

    /* ── Signatures ───────────────────────────────────────────────────── */

    /* A table, not floats. Dompdf ignores float often enough that a
       three-column table is the only layout that reliably holds. The middle
       column is empty -- it reserves the seal's space. */
    .sig-table {
        position: absolute;
        top: 228mm;
        left: 16mm;
        width: 178mm;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sig-table td {
        vertical-align: bottom;
        text-align: center;
        font-family: "DejaVu Serif", serif;
    }

    .sig-name  { font-size: 10.5pt; font-weight: bold; letter-spacing: .2pt; }
    .sig-title { font-size: 10pt;   font-weight: bold; }

    /* The scanned signature overlaps the printed name the way ink does on
       paper. A negative margin is the only way to get that in Dompdf. */
    .sig-ink { height: 12mm; margin-bottom: -1.5mm; }

    /* ── Verification ─────────────────────────────────────────────────── */

    /*
      The QR and the serial are what make this document checkable. They sit
      in the bottom-left corner inside the frame, where the original carries
      them, clear of the signature strip above.
    */
    .qr-block {
        position: absolute;
        top: 255mm;
        left: 18mm;
        width: 28mm;
        text-align: center;
    }

    .qr-block img { width: 19mm; height: 19mm; }

    .qr-serial {
        margin-top: 1mm;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 5.5pt;
        letter-spacing: .3pt;
        color: #333;
    }

    .resolution {
        position: absolute;
        top: 259mm;
        left: 25mm;
        width: 160mm;
        text-align: right;
        font-family: "DejaVu Serif", serif;
        font-size: 10.5pt;
        line-height: 1.5;
    }

    .resolution em { font-style: italic; font-weight: bold; }

    /* Printed small and quiet. Not for reading -- it is there so a printed
       copy still carries what the QR encodes, for anyone checking by hand
       through the public portal. */
    .hash-line {
        position: absolute;
        top: 288mm;
        left: 25mm;
        width: 160mm;
        text-align: center;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 4.5pt;
        color: #9aa2b5;
    }
</style>
</head>
<body>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($border): ?>
    <img class="border-art" src="<?php echo e($border); ?>" alt="">
<?php else: ?>
    <div class="border-rule"></div>
    <div class="border-rule-inner"></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>



<div class="at fraktur t-republic" style="top:27.3mm">Republic of the Philippines</div>
<div class="at fraktur t-uni"      style="top:35mm">Partido State University</div>
<div class="at roman   t-province" style="top:47.4mm">Camarines Sur</div>

<div class="at roman t-presents" style="top:59.3mm">
    TO ALL PERSONS TO WHOM THESE PRESENTS MAY COME:
</div>

<div class="at fraktur t-greetings" style="top:69mm">Greetings</div>



<div class="at roman t-beitknown" style="top:88mm">
    BE IT KNOWN <span class="script">that the</span> BOARD OF REGENTS <span class="script">of</span><br>
    PARTIDO STATE UNIVERSITY
</div>

<div class="at script t-recommend" style="top:99.8mm">
    by authority of the Republic of the Philippines and on the recommendation<br>
    of the Academic Council has conferred upon
</div>

<div class="at fraktur" style="top:116.8mm;font-size:<?php echo e($nameSize); ?>pt"><?php echo e($fullName); ?></div>

<div class="at script t-fulfilled" style="top:133mm">
    who has fulfilled all the requirements thereof the degree of
</div>

<div class="at script" style="top:143mm;font-size:<?php echo e($degreeSize); ?>pt"><?php echo e($degree); ?></div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($honor): ?>
    
    <div class="at script t-honor" style="top:152.5mm"><?php echo e($honor); ?></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="at script t-rights" style="top:158.2mm">
    with all the rights, honors and privileges as well as the obligations<br>
    and responsibilities thereunto appertaining.
</div>



<div class="at roman t-testimony" style="top:175.4mm">IN TESTIMONY WHEREOF,</div>

<div class="at script t-seal-line" style="top:182.1mm">
    the seal of the <span class="roman" style="font-size:12.5pt">PARTIDO STATE UNIVERSITY</span> and the signatures of<br>
    the President and the University Registrar are hereunto affixed.
</div>

<div class="at script t-given" style="top:199.4mm">
    Given at Goa, Camarines Sur, Philippines this <?php echo e($dayNumber); ?><span class="ordinal"><?php echo e($daySuffix); ?></span> day of <?php echo e($monthName); ?><br>
    in the year of our Lord, <?php echo e($yearName); ?>.
</div>



<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($seal): ?>
    <div class="seal-wrap"><img src="<?php echo e($seal); ?>" alt=""></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<table class="sig-table">
    <tr>
        <td style="width:60mm">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($signRegist): ?><img class="sig-ink" src="<?php echo e($signRegist); ?>" alt=""><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </td>
        <td style="width:58mm"></td>
        <td style="width:60mm">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($signPres): ?><img class="sig-ink" src="<?php echo e($signPres); ?>" alt=""><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="sig-name"><?php echo e($registrar); ?></td>
        <td></td>
        <td class="sig-name"><?php echo e($president); ?></td>
    </tr>
    <tr>
        <td class="sig-title"><?php echo e($registrarT); ?></td>
        <td></td>
        <td class="sig-title"><?php echo e($presidentT); ?></td>
    </tr>
</table>



<div class="qr-block">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($qr)): ?><img src="<?php echo e($qr); ?>" alt=""><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="qr-serial"><?php echo e($certificate->serial_number); ?></div>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resolutionNo || $resolutionDate): ?>
    <div class="resolution">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resolutionNo): ?>
            Board Resolution No. <em><?php echo e($resolutionNo); ?></em><br>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resolutionDate): ?>
            Date: <em><?php echo e($resolutionDate); ?></em>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="hash-line">SHA-256 <?php echo e($certificate->content_hash ?? ''); ?></div>

</body>
</html><?php /**PATH C:\laragon\www\celeste\resources\views/pdf/diploma.blade.php ENDPATH**/ ?>