<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Serif', Georgia, serif;
            margin: 0;
            color: #12224f;
        }
        .sheet { padding: 34px 46px; position: relative; }
        .border-outer {
            border: 3px double #12224f;
            padding: 26px 40px 20px;
            height: 500px;
            position: relative;
        }
        .seal { width: 74px; }
        .institution {
            font-size: 19pt;
            letter-spacing: 3px;
            font-weight: bold;
            margin: 6px 0 2px;
        }
        .campus { font-size: 9.5pt; letter-spacing: 2px; color: #24417f; }
        .republic { font-size: 8.5pt; letter-spacing: 1px; color: #5b6784; }
        .title {
            font-size: 26pt;
            letter-spacing: 6px;
            margin: 18px 0 10px;
            font-weight: bold;
        }
        .lead { font-size: 10.5pt; line-height: 1.7; color: #16233f; }
        .name {
            font-size: 24pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 12px 0 8px;
            border-bottom: 1px solid #c8d2e4;
            display: inline-block;
            padding: 0 30px 4px;
        }
        .degree { font-size: 15pt; font-weight: bold; margin: 8px 0 4px; }
        .honor { font-size: 11pt; font-style: italic; color: #96650f; }
        .sig-line { border-top: 1px solid #12224f; width: 190px; margin: 0 auto; padding-top: 4px; }
        .sig-name { font-size: 9.5pt; font-weight: bold; }
        .sig-role { font-size: 8pt; color: #5b6784; }
        .qr-block { position: absolute; bottom: 14px; right: 22px; text-align: center; width: 108px; }
        .qr-block img { width: 84px; height: 84px; }
        .qr-caption { font-family: 'DejaVu Sans', sans-serif; font-size: 5.6pt; color: #5b6784; line-height: 1.35; margin-top: 2px; }
        .serial { font-family: 'DejaVu Sans Mono', monospace; font-size: 6pt; color: #12224f; }
        .hash { font-family: 'DejaVu Sans Mono', monospace; font-size: 4.6pt; color: #8a94ad; word-wrap: break-word; }
        .footer-left {
            position: absolute; bottom: 16px; left: 22px;
            font-family: 'DejaVu Sans', sans-serif; font-size: 6pt; color: #8a94ad; width: 300px;
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="border-outer">
        <div style="text-align:center">
            <div class="republic">Republic of the Philippines</div>
            <div class="institution"><?php echo e(mb_strtoupper($payload['institution'])); ?></div>
            <div class="campus"><?php echo e(mb_strtoupper($payload['campus'])); ?></div>

            <div class="title">DIPLOMA</div>

            <div class="lead">By virtue of the authority vested in it by law, and upon the recommendation<br>
                of the University Council, this institution confers upon</div>

            <div class="name"><?php echo e($payload['full_name']); ?></div>

            <div class="lead">who has satisfactorily completed all the requirements prescribed for the degree of</div>

            <div class="degree"><?php echo e(mb_strtoupper($payload['program'])); ?></div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($payload['major'])): ?>
                <div class="lead">major in <?php echo e($payload['major']); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($payload['latin_honor'])): ?>
                <div class="honor"><?php echo e($payload['latin_honor']); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="lead" style="margin-top:12px">
                with all the rights, honors, and privileges appertaining thereto.
            </div>

            <div class="lead" style="margin-top:10px">
                Given at <?php echo e($payload['campus']); ?>, Philippines, this
                <?php echo e(\Illuminate\Support\Carbon::parse($payload['issued_on'])->format('jS \d\a\y \o\f F, Y')); ?>.
            </div>
        </div>

        <table width="100%" style="margin-top:36px" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" align="center">
                    <div class="sig-line">
                        <div class="sig-name"><?php echo e(config('celeste.officials.registrar')); ?></div>
                        <div class="sig-role">University Registrar</div>
                    </div>
                </td>
                <td width="50%" align="center">
                    <div class="sig-line">
                        <div class="sig-name"><?php echo e(config('celeste.officials.president')); ?></div>
                        <div class="sig-role">University President</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer-left">
            Verify at <?php echo e(config('app.url')); ?>/verify &nbsp;·&nbsp; Student No. <?php echo e($payload['student_number']); ?><br>
            <span class="hash">SHA-256 <?php echo e($certificate->content_hash); ?></span>
        </div>

        <div class="qr-block">
            <img src="<?php echo e($qr); ?>" alt="Verification QR code">
            <div class="serial"><?php echo e($certificate->serial_number); ?></div>
            <div class="qr-caption">Scan to verify this diploma</div>
        </div>
    </div>
</div>
</body>
</html>
<?php /**PATH C:\laragon\www\celeste\resources\views/pdf/diploma.blade.php ENDPATH**/ ?>