@php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    $money = fn ($n) => number_format((float) $n, 2);

    // 21.0 -> "21", 175.50 -> "175.5": how the form writes "21 x 175/unit".
    $plain = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');

    // A missing asset leaves a gap rather than throwing.
    $asset = function (string $relative) {
        $path = public_path($relative);
        return is_file($path) ? $path : null;
    };
    $seal = $asset('images/psu-seal.png');

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  THE LOGIC OF THE FORM
      | ─────────────────────────────────────────────────────────────────────
      |  Read off the printed form, every figure follows from the subjects and
      |  from a short list of rates:
      |
      |    total units          = sum of the Units column            (21.0)
      |    tuition              = total units x rate per unit        (21 x 175/unit)
      |    computer fee         = subjects with a lab x rate         (5 x 350/subject)
      |    miscellaneous total  = sum of the fixed fee list          (630.00)
      |    total assessment     = tuition + computer + miscellaneous (6,055.00)
      |    scholarship          = what the scholarship covers        (UNIFAST: all)
      |    discounted           = assessment - scholarship           (0.00)
      |    current receivable   = discounted + previous - downpayment
      |
      |  The payload stores the subjects and the RATES, not the results, so a
      |  total can never disagree with the lines above it, and the same payload
      |  always prints the same form. A payload that carries an explicit
      |  tuition or laboratory amount is honoured instead of the rate, for
      |  forms whose fee was set by hand.
    */
    $subjects = $p['subjects'] ?? [];

    $units = $tf = $lab = 0.0;
    $labSubjects = 0;
    foreach ($subjects as $s) {
        $units += (float) ($s['units'] ?? 0);
        $tf    += (float) ($s['tf']    ?? 0);
        $lab   += (float) ($s['lab']   ?? 0);
        if ((float) ($s['lab'] ?? 0) > 0) $labSubjects++;
    }

    $tuitionRate = (float) ($p['rates']['tuition_per_unit']   ?? 0);
    $labRate     = (float) ($p['rates']['lab_per_subject']    ?? 0);

    $tuition = isset($p['tuition']['amount'])    ? (float) $p['tuition']['amount']    : $units * $tuitionRate;
    $labFee  = isset($p['laboratory']['amount']) ? (float) $p['laboratory']['amount'] : $labSubjects * $labRate;

    $tuitionBasis = $p['tuition']['basis']    ?? ($plain($units) . ' x ' . $plain($tuitionRate) . '/unit');
    $labBasis     = $p['laboratory']['basis'] ?? ($plain($labSubjects) . ' x ' . $plain($labRate) . '/subject');

    $misc    = $p['misc_fees'] ?? [];
    $miscSum = array_sum(array_map(fn ($f) => (float) ($f['amount'] ?? 0), $misc));

    $assessment = $tuition + $labFee + $miscSum;

    // A scholarship either lists what it covers, or says it covers everything,
    // in which case the list is every fee on the form.
    $scholar     = $p['scholarship'] ?? null;
    $scholarName = is_array($scholar) ? ($scholar['name'] ?? '') : '';
    $scholarRows = is_array($scholar) ? ($scholar['items'] ?? []) : [];
    if (is_array($scholar) && ! $scholarRows && ($scholar['coverage'] ?? null) === 'full') {
        $scholarRows = array_merge(
            [['name' => 'Tuition Fee',  'amount' => $tuition],
             ['name' => 'Computer Fee', 'amount' => $labFee]],
            $misc
        );
    }
    $scholarSum = array_sum(array_map(fn ($f) => (float) ($f['amount'] ?? 0), $scholarRows));

    $discounted  = max(0, $assessment - $scholarSum);
    $previous    = (float) ($p['previous_balance'] ?? 0);
    $downpayment = (float) ($p['downpayment'] ?? 0);
    $receivable  = max(0, $discounted + $previous - $downpayment);

    // "BSIT 3": the course code and the year number, as the form prints them.
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
      | ─────────────────────────────────────────────────────────────────────
      |  GEOMETRY
      | ─────────────────────────────────────────────────────────────────────
      |  Taken off a scan of the printed PSU-F-URO-24, which is Letter
      |  portrait (215.9 x 279.4mm). The scan's margins are 24.6mm left and
      |  18.7mm right, so the body is 172.6mm wide and every column below is
      |  cut to that. The right margin is NOT set on the page: the form
      |  number sits 8.7mm into it, and anything that overflows the page area
      |  makes the renderer shrink the whole page to fit. The body is held to
      |  172.6mm by a wrapper instead. Column widths are in millimetres, measured from
      |  the left edge of the table on the scan, and each table is
      |  table-layout:fixed so a long subject name wraps inside its cell
      |  instead of widening it.
      |
      |  Type is Arial on the printed form. Dompdf maps Arial to Helvetica,
      |  which has the same metrics, so line breaks fall where they do on the
      |  paper. Sizes were found by measuring the width of known strings on
      |  the scan against the same strings set in Arial:
      |
      |    letterhead  8.4 / 9.2 / 8.7pt     title       9.4pt
      |    name block  8.6-8.8pt bold        subject rows 7.5pt
      |    assessment  8.1pt                 headings    8.0-8.5pt bold
      |
      |  Rows are a fixed height (line-height in mm), because the printed
      |  form is ruled on a grid and blank rows must take exactly one row.
      |
      |  The letterhead is pinned (position:absolute) at the measured spot,
      |  and a spacer of fixed height sits where it is, so the body starts at
      |  the same place however the letterhead renders.
    */
    @page { size: 215.9mm 279.4mm; margin: 5mm 0 6mm 24.6mm; }

    body { font-family: Arial, Helvetica, sans-serif; font-size: 8.1pt; color: #000; margin: 0; }

    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td, th { padding: 0; vertical-align: top; text-align: left; font-weight: normal; }

    .nw { white-space: nowrap; }

    /* name block */
    .info td { line-height: 3.9mm; font-size: 8.8pt; font-weight: bold; white-space: nowrap; }
    .info td.l { font-size: 8.6pt; }

    /* subjects */
    .subj { border-bottom: 1.1pt solid #000; }
    .subj th { font-size: 8pt; font-weight: bold; line-height: 3.7mm;
               border-top: 1.1pt solid #000; border-bottom: .9pt solid #000; }
    .subj td { font-size: 7.5pt; line-height: 3.18mm; border-bottom: .5pt solid #000; }
    .subj tr.total td { font-size: 8pt; font-weight: bold; border-bottom: 0; }

    /* Width is the CONTENT width. A padded first-row cell is that much wider in a
       fixed-layout table, so the header cells below are cut short by their own
       padding to keep each column at its measured width. */
    /* assessment: every row, blank or not, is one grid row */
    .assess td, .assess th { line-height: 3.35mm; font-size: 8.1pt; white-space: nowrap; }
    .assess th { font-weight: bold; font-size: 8.5pt; }
    .assess .r { text-align: right; }
    .assess .c { text-align: center; }
    .assess .b { font-weight: bold; }
    .assess .i { padding-left: 4.4mm; }

    /* summary and payment schedule */
    .box { border-top: 1.1pt solid #000; }
    .box th { font-size: 8.5pt; font-weight: bold; text-align: center; line-height: 3.5mm;
              border-bottom: .9pt solid #000; }
    .box td { line-height: 3.4mm; font-size: 8.1pt; border-bottom: .5pt solid #000; white-space: nowrap; }
    .box .r { text-align: right; }
</style>
</head>
<body>

{{-- ───────────── Letterhead (pinned) ───────────── --}}
@if ($seal)
    <img src="{{ $seal }}" alt="" style="position:absolute; left:42.5mm; top:1.2mm; width:15.9mm">
@endif

<div style="position:absolute; left:55.3mm; top:1.45mm; width:70mm; text-align:center; line-height:4.3mm">
    <div style="font-size:8.4pt; font-weight:bold">Republic of the Philippines</div>
    <div style="font-size:9.2pt; font-weight:bold">{{ config('celeste.institution.name', 'Partido State University') }}</div>
    <div style="font-size:8.7pt">Goa, {{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
</div>

<div style="position:absolute; left:140mm; top:12.8mm; width:41.3mm; text-align:right; font-size:9pt; line-height:3.4mm">
    PSU-F-URO-24
</div>

<div style="position:absolute; left:40.7mm; top:17.5mm; width:100mm; text-align:center;
            font-size:9.4pt; line-height:4.2mm; color:#12306B">
    ENROLLMENT AND ASSESSMENT FORM
</div>

<div style="width:172.6mm">

{{-- Spacer: the body begins 23.4mm below the top margin, as on the form. --}}
<div style="height:23.4mm; font-size:0; line-height:0">&nbsp;</div>

{{-- ───────────── Student and period ───────────── --}}
<table class="info">
    <tr>
        <td class="l" style="width:11.6mm">Name:</td>
        <td style="width:57.4mm">{{ $v('full_name') }}</td>
        <td style="width:16.6mm"></td>
        <td style="width:32.8mm"></td>
        <td class="l" style="width:11.8mm">Period:</td>
        <td style="width:42.4mm">{{ $period }}</td>
    </tr>
    <tr>
        <td class="l">ID No:</td>
        <td>{{ $v('student_number') }}</td>
        <td class="l">Course/Yr:</td>
        <td>{{ $course }}</td>
        <td class="l">Date:</td>
        <td>{{ $d('enrolled_on') ?: $d('issued_on') }}</td>
    </tr>
</table>

{{-- ───────────── Subjects ───────────── --}}
<table class="subj" style="margin-top:2.3mm">
    <tr>
        <th style="width:8.6mm">Code</th>
        <th style="width:21.7mm">Subject Code</th>
        <th style="width:42.9mm">Subject Description</th>
        <th style="width:8.4mm">Units</th>
        <th style="width:6.1mm">TF</th>
        <th style="width:6.1mm">Lab</th>
        <th style="width:37.8mm">Schedule</th>
        <th style="width:20.2mm">Instructor</th>
        <th style="width:20.8mm">Section</th>
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
                    <td style="padding-left:1.1mm">{{ number_format((float) ($s['units'] ?? 0), 1) }}</td>
                    <td>{{ number_format((float) ($s['tf'] ?? 0), 1) }}</td>
                    <td>{{ number_format((float) ($s['lab'] ?? 0), 1) }}</td>
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

    <tr class="total">
        <td colspan="3" style="text-align:center">Total Units:</td>
        <td style="padding-left:.4mm">{{ number_format($units, 1) }}</td>
        <td>{{ number_format($tf, 1) }}</td>
        <td>{{ number_format($lab, 1) }}</td>
        <td colspan="3"></td>
    </tr>
</table>

{{-- ───────────── Assessment ───────────── --}}
<table class="assess" style="margin-top:2mm">
    <tr>
        <th style="width:12.7mm"></th>
        <th style="width:14.6mm; padding-left:37.8mm">Assessment</th>
        <th style="width:21mm"></th>
        <th style="width:15.4mm; padding-left:25.7mm">Amount</th>
        <th style="width:14.1mm; padding-left:9mm">Total</th>
        <th style="width:22.3mm"></th>
    </tr>

    <tr><td></td><td colspan="5">Tuition Fees</td></tr>
    <tr>
        <td></td><td class="i">Tuition Fee</td>
        <td class="c">{{ $tuitionBasis }}</td>
        <td class="r">{{ $money($tuition) }}</td>
        <td class="r">{{ $money($tuition) }}</td>
        <td></td>
    </tr>

    <tr><td colspan="6">&nbsp;</td></tr>
    <tr><td></td><td colspan="5">Laboratory Fees</td></tr>
    <tr>
        <td></td><td class="i">Computer Fee</td>
        <td class="c">{{ $labBasis }}</td>
        <td class="r">{{ $money($labFee) }}</td>
        <td class="r">{{ $money($labFee) }}</td>
        <td></td>
    </tr>

    <tr><td colspan="6">&nbsp;</td></tr>
    <tr><td></td><td colspan="5">Miscellaneous Fees</td></tr>
    @foreach ($misc as $f)
        <tr>
            <td></td><td class="i">{{ $f['name'] ?? '' }}</td><td></td>
            <td class="r">{{ $money($f['amount'] ?? 0) }}</td>
            <td class="r">{{ $loop->last ? $money($miscSum) : '' }}</td>
            <td></td>
        </tr>
    @endforeach

    <tr><td colspan="6">&nbsp;</td></tr>
    <tr>
        <td></td><td colspan="3" class="b">Total Assessment</td>
        <td class="r b">{{ $money($assessment) }}</td><td></td>
    </tr>

    @if ($scholarRows)
        <tr><td colspan="6">&nbsp;</td></tr>
        <tr><td></td><td colspan="5">Discounts/Scholarships:</td></tr>
        <tr>
            <td></td>
            <td>
                <table><tr>
                    <td>{{ $scholarName }}</td>
                    <td style="width:2.7mm; text-align:right">:</td>
                </tr></table>
            </td>
            <td colspan="4"></td>
        </tr>
        @foreach ($scholarRows as $f)
            <tr>
                <td></td><td class="i">{{ $f['name'] ?? '' }}</td><td></td>
                <td class="r">{{ $money($f['amount'] ?? 0) }}</td>
                <td class="r">{{ $loop->last ? $money($scholarSum) : '' }}</td>
                <td></td>
            </tr>
        @endforeach

        <tr><td colspan="6">&nbsp;</td></tr>
        <tr>
            <td></td><td colspan="3" class="b">Total Discounts/Scholarships</td>
            <td class="r b">{{ $money($scholarSum) }}</td><td></td>
        </tr>
    @endif

    <tr><td colspan="6">&nbsp;</td></tr>
    <tr>
        <td></td><td colspan="3" class="b">Discounted Assessment</td>
        <td class="r b">{{ $money($discounted) }}</td><td></td>
    </tr>
</table>

{{-- ───────────── Summary and payment schedule ───────────── --}}
<table style="margin-top:2.2mm">
    <tr>
        <td style="width:.8mm"></td>

        <td style="width:72.4mm">
            <table class="box">
                <tr>
                    <th style="width:35.7mm">Summary</th>
                    <th style="width:19.4mm">Amount</th>
                    <th style="width:17.3mm">Total</th>
                </tr>
                <tr><td>Current Assessment</td><td class="r">{{ $money($assessment) }}</td><td></td></tr>
                <tr><td>Discounts/Scholarships</td><td class="r">({{ $money($scholarSum) }})</td><td></td></tr>
                <tr><td>Previous Balance</td><td class="r">{{ $money($previous) }}</td><td></td></tr>
                <tr><td>Current Receivable</td><td></td><td class="r">{{ $money($receivable) }}</td></tr>
            </table>
        </td>

        <td style="width:3.2mm"></td>

        <td style="width:96.2mm">
            <table class="box">
                <tr>
                    <th style="width:55.6mm">Payment Schedule</th>
                    <th style="width:22.8mm">Amount</th>
                    <th style="width:17.8mm">Total</th>
                </tr>
                <tr><td>Downpayment</td><td class="r">{{ $money($downpayment) }}</td><td class="r">{{ $money($downpayment) }}</td></tr>
            </table>

            {{-- The verification block, at the foot of the page where the printed
                 form carries its stamp. The Processed-by line and the ENROLLED
                 stamp are not reproduced: they record the paper transaction, and
                 the QR code and serial are what vouch for this copy. --}}
            <table style="margin-top:1.4mm">
                <tr>
                    <td style="width:15mm; vertical-align:top">
                        @if (!empty($qr))
                            <img src="{{ $qr }}" style="width:14mm;height:14mm" alt="">
                        @else
                            <div style="width:14mm;height:14mm;border:.5pt dashed #999;font-size:5pt;color:#999;text-align:center">
                                <div style="padding-top:5.5mm">QR</div>
                            </div>
                        @endif
                    </td>
                    <td style="vertical-align:middle; padding-left:1.5mm">
                        <div style="font-family:'Courier',monospace; font-size:6.4pt">{{ $certificate->serial_number }}</div>
                        <div style="font-size:5.8pt; color:#444; padding-top:.6mm">Scan to verify</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</div>

</body>
</html>