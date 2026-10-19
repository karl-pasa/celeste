@php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    // Money is stored as a string at fixed precision (see the payload notes),
    // and only formatted here. Zero prints as 0.00, not as a blank, because the
    // printed form shows 0.00 for a fully discounted assessment.
    $money = fn ($n) => number_format((float) $n, 2);

    // A missing asset leaves a gap rather than throwing.
    $asset = function (string $relative) {
        $path = public_path($relative);
        return is_file($path) ? $path : null;
    };
    $seal = $asset('images/psu-seal.png');

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  WHAT IS READ FROM THE PAYLOAD, AND WHAT IS WORKED OUT
      | ─────────────────────────────────────────────────────────────────────
      |  Every figure the form prints is either stored in the payload or is a
      |  sum of figures that are. Totals are not stored separately: a stored
      |  total could disagree with the lines above it, and the fingerprint
      |  would then vouch for a form that does not add up. Worked out here,
      |  the same payload always prints the same totals.
    */
    $subjects = $p['subjects'] ?? [];

    $units = $tf = $lab = 0.0;
    foreach ($subjects as $s) {
        $units += (float) ($s['units'] ?? 0);
        $tf    += (float) ($s['tf']    ?? 0);
        $lab   += (float) ($s['lab']   ?? 0);
    }

    $tuition = (float) ($p['tuition']['amount']    ?? 0);
    $labFee  = (float) ($p['laboratory']['amount'] ?? 0);
    $misc    = $p['misc_fees'] ?? [];
    $miscSum = array_sum(array_map(fn ($f) => (float) ($f['amount'] ?? 0), $misc));

    $assessment = $tuition + $labFee + $miscSum;

    $scholar     = $p['scholarship'] ?? null;
    $scholarName = is_array($scholar) ? ($scholar['name'] ?? '') : '';
    $scholarRows = is_array($scholar) ? ($scholar['items'] ?? []) : [];
    $scholarSum  = array_sum(array_map(fn ($f) => (float) ($f['amount'] ?? 0), $scholarRows));

    $discounted  = max(0, $assessment - $scholarSum);
    $previous    = (float) ($p['previous_balance'] ?? 0);
    $downpayment = (float) ($p['downpayment'] ?? 0);
    $receivable  = max(0, $discounted + $previous - $downpayment);

    // "BSIT 3" -- the course code and the year number, as the form prints
    // them together. Falls back to the programme name if no code was stored.
    $yearNo = preg_match('/\d+/', (string) ($p['year_level'] ?? ''), $m) ? $m[0] : ($p['year_level'] ?? '');
    $course = trim(($p['course_code'] ?? $p['program'] ?? '') . ' ' . $yearNo);

    $period = trim(($p['semester'] ?? '') . (filled($p['academic_year'] ?? null) ? ' AY ' . $p['academic_year'] : ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $certificate->serial_number }}</title>
<style>
    /*
      | Page and measure. A4 portrait with 12mm side margins leaves 186mm,
      | which the subject table and the assessment table below are both cut
      | to. Column widths are fixed (table-layout:fixed) so a long subject
      | description wraps inside its cell instead of widening it and pushing
      | the schedule column off the page.
    */
    @page { size: 210mm 297mm; margin: 9mm 12mm; }

    /* DejaVu ships with Dompdf and carries the n-tilde that student and
       instructor names need, which the core fonts do not. */
    body { font-family: "DejaVu Sans", sans-serif; font-size: 7.4pt; color: #000; margin: 0; }

    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td, th { vertical-align: top; padding: 0; }

    .h-rep  { font-size: 9pt; }
    .h-uni  { font-size: 10pt; font-weight: bold; }
    .h-camp { font-size: 8.5pt; }
    .formno { font-size: 8.5pt; text-align: right; }
    .title  { font-size: 10.5pt; color: #1F4E79; text-align: center; margin-top: 3mm; }

    .lbl { font-weight: bold; }
    .big { font-size: 9pt; font-weight: bold; }

    /* The subject list is ruled above, below and between rows. */
    .subj { margin-top: 2.5mm; border-top: 1pt solid #000; border-bottom: 1pt solid #000; }
    .subj th { font-size: 7.6pt; text-align: left; padding: .3mm 0; border-bottom: 1pt solid #000; }
    .subj td { padding: .2mm 0; border-bottom: .6pt solid #000; }
    .subj .n { text-align: center; }

    .money { text-align: right; }

    .assess th { font-size: 9pt; padding-bottom: 1mm; }
    .assess td { padding: .12mm 0; font-size: 7.4pt; }
    .assess .grp  { padding-top: 1.6mm; }
    .assess .item { padding-left: 4mm; }
    .assess .sum  { font-weight: bold; padding-top: 1.6mm; }

    .box { border-top: 1pt solid #000; border-bottom: 1pt solid #000; }
    .box th { font-size: 8.5pt; padding: .3mm 0; border-bottom: 1pt solid #000; }
    .box td { padding: .3mm 0; font-size: 7.6pt; border-bottom: .6pt solid #000; }

    .mono { font-family: "DejaVu Sans Mono", monospace; }
</style>
</head>
<body>

{{-- ───────────── Letterhead ───────────── --}}
<table>
    <tr>
        <td style="width:30%"></td>
        <td style="width:44%; text-align:center">
            <table>
                <tr>
                    <td style="width:19mm; padding-right:2mm; vertical-align:middle">
                        @if ($seal)<img src="{{ $seal }}" style="width:17mm;height:17mm" alt="">@endif
                    </td>
                    <td style="text-align:center; vertical-align:middle">
                        <div class="h-rep">Republic of the Philippines</div>
                        <div class="h-uni">{{ config('celeste.institution.name', 'Partido State University') }}</div>
                        <div class="h-camp">Goa, {{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:26%" class="formno">PSU-F-URO-24</td>
    </tr>
</table>

<div class="title">ENROLLMENT AND ASSESSMENT FORM</div>

{{-- ───────────── Student and period ───────────── --}}
<table style="margin-top:3mm">
    <tr>
        <td style="width:13mm" class="lbl">Name:</td>
        <td style="width:56mm" class="big">{{ $v('full_name') }}</td>
        <td style="width:20mm"></td>
        <td style="width:28mm"></td>
        <td style="width:14mm" class="lbl">Period:</td>
        <td class="big">{{ $period }}</td>
    </tr>
    <tr>
        <td class="lbl" style="padding-top:.8mm">ID No:</td>
        <td class="big" style="padding-top:.8mm">{{ $v('student_number') }}</td>
        <td class="lbl" style="padding-top:.8mm">Course/Yr:</td>
        <td class="big" style="padding-top:.8mm">{{ $course }}</td>
        <td class="lbl" style="padding-top:.8mm">Date:</td>
        <td class="big" style="padding-top:.8mm">{{ $d('enrolled_on') ?: $d('issued_on') }}</td>
    </tr>
</table>

{{-- ───────────── Subjects ───────────── --}}
<table class="subj">
    <tr>
        <th style="width:10mm">Code</th>
        <th style="width:22mm">Subject Code</th>
        <th style="width:44mm">Subject Description</th>
        <th style="width:9mm" class="n">Units</th>
        <th style="width:8mm" class="n">TF</th>
        <th style="width:8mm" class="n">Lab</th>
        <th style="width:38mm">Schedule</th>
        <th style="width:27mm">Instructor</th>
        <th style="width:22mm">Section</th>
    </tr>

    @foreach ($subjects as $s)
        @php
            $lines = $s['schedule'] ?? [];
            if (! $lines) $lines = [''];
        @endphp
        @foreach ($lines as $i => $line)
            <tr>
                @if ($i === 0)
                    <td>{{ $s['code'] ?? '' }}</td>
                    <td>{{ $s['subject_code'] ?? '' }}</td>
                    <td>{{ $s['description'] ?? '' }}</td>
                    <td class="n">{{ number_format((float) ($s['units'] ?? 0), 1) }}</td>
                    <td class="n">{{ number_format((float) ($s['tf'] ?? 0), 1) }}</td>
                    <td class="n">{{ number_format((float) ($s['lab'] ?? 0), 1) }}</td>
                @else
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                @endif
                <td>{{ $line }}</td>
                @if ($i === 0)
                    <td>{{ $s['instructor'] ?? '' }}</td>
                    <td>{{ $s['section'] ?? '' }}</td>
                @else
                    <td></td><td></td>
                @endif
            </tr>
        @endforeach
    @endforeach

    <tr>
        <td colspan="3" style="text-align:center; font-weight:bold; border-bottom:0; padding-top:.6mm">Total Units:</td>
        <td class="n" style="font-weight:bold; border-bottom:0; padding-top:.6mm">{{ number_format($units, 1) }}</td>
        <td class="n" style="font-weight:bold; border-bottom:0; padding-top:.6mm">{{ number_format($tf, 1) }}</td>
        <td class="n" style="font-weight:bold; border-bottom:0; padding-top:.6mm">{{ number_format($lab, 1) }}</td>
        <td colspan="3" style="border-bottom:0"></td>
    </tr>
</table>

{{-- ───────────── Assessment ───────────── --}}
<table class="assess" style="margin-top:2mm">
    <tr>
        <th style="width:58mm; text-align:center">Assessment</th>
        <th style="width:38mm"></th>
        <th style="width:30mm; text-align:center">Amount</th>
        <th style="width:30mm; text-align:center">Total</th>
        <th></th>
    </tr>

    <tr>
        <td colspan="4" class="grp">Tuition Fees</td>
        <td rowspan="5" style="text-align:center; padding-top:1.6mm">
            @if (!empty($qr))
                <img src="{{ $qr }}" style="width:19mm;height:19mm" alt="">
            @else
                <div style="width:19mm;height:19mm;border:.5pt dashed #999;font-size:5pt;color:#999;margin:0 auto">
                    <div style="padding-top:7mm">QR</div>
                </div>
            @endif
            <div class="mono" style="font-size:5.4pt; padding-top:.6mm">{{ $certificate->serial_number }}</div>
            <div style="font-size:5.4pt; color:#444">Scan to verify</div>
        </td>
    </tr>
    <tr>
        <td class="item">Tuition Fee</td>
        <td style="text-align:center">{{ $p['tuition']['basis'] ?? '' }}</td>
        <td class="money">{{ $money($tuition) }}</td>
        <td class="money">{{ $money($tuition) }}</td>
    </tr>

    <tr><td colspan="4" class="grp">Laboratory Fees</td></tr>
    <tr>
        <td class="item">Computer Fee</td>
        <td style="text-align:center">{{ $p['laboratory']['basis'] ?? '' }}</td>
        <td class="money">{{ $money($labFee) }}</td>
        <td class="money">{{ $money($labFee) }}</td>
    </tr>

    <tr><td colspan="4" class="grp">Miscellaneous Fees</td></tr>
    @foreach ($misc as $i => $f)
        <tr>
            <td class="item">{{ $f['name'] ?? '' }}</td>
            <td></td>
            <td class="money">{{ $money($f['amount'] ?? 0) }}</td>
            <td class="money">{{ $loop->last ? $money($miscSum) : '' }}</td>
            <td></td>
        </tr>
    @endforeach

    <tr>
        <td colspan="3" class="sum">Total Assessment</td>
        <td class="money sum">{{ $money($assessment) }}</td>
        <td></td>
    </tr>

    @if ($scholarRows)
        <tr><td colspan="5" class="grp">Discounts/Scholarships:</td></tr>
        <tr><td colspan="5" style="padding-top:.3mm">{{ $scholarName }}</td></tr>
        @foreach ($scholarRows as $f)
            <tr>
                <td class="item">{{ $f['name'] ?? '' }}</td>
                <td></td>
                <td class="money">{{ $money($f['amount'] ?? 0) }}</td>
                <td class="money">{{ $loop->last ? $money($scholarSum) : '' }}</td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" class="sum">Total Discounts/Scholarships</td>
            <td class="money sum">{{ $money($scholarSum) }}</td>
            <td></td>
        </tr>
    @endif

    <tr>
        <td colspan="3" class="sum">Discounted Assessment</td>
        <td class="money sum">{{ $money($discounted) }}</td>
        <td></td>
    </tr>
</table>

{{-- ───────────── Summary and payment schedule ───────────── --}}
<table style="margin-top:3mm">
    <tr>
        <td style="width:90mm; padding-right:4mm">
            <table class="box">
                <tr>
                    <th style="width:38mm; text-align:center">Summary</th>
                    <th style="width:26mm; text-align:center">Amount</th>
                    <th style="text-align:center">Total</th>
                </tr>
                <tr><td>Current Assessment</td><td class="money">{{ $money($assessment) }}</td><td></td></tr>
                <tr><td>Discounts/Scholarships</td><td class="money">({{ $money($scholarSum) }})</td><td></td></tr>
                <tr><td>Previous Balance</td><td class="money">{{ $money($previous) }}</td><td></td></tr>
                <tr><td>Current Receivable</td><td></td><td class="money">{{ $money($receivable) }}</td></tr>
            </table>
        </td>
        <td style="width:96mm">
            <table class="box">
                <tr>
                    <th style="text-align:center">Payment Schedule</th>
                    <th style="width:26mm; text-align:center">Amount</th>
                    <th style="width:22mm; text-align:center">Total</th>
                </tr>
                <tr><td>Downpayment</td><td class="money">{{ $money($downpayment) }}</td><td class="money">{{ $money($downpayment) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Page note. The Processed-by line and the ENROLLED stamp of the printed
     form are not reproduced; the QR code and serial beside the assessment
     are what vouch for this copy. --}}
<div style="margin-top:4mm; font-size:7pt">Page 1 / 1</div>

</body>
</html>