@php
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
      |  Taken off a scan of the blank PSU-F-URO-23. All figures are
      |  millimetres from the top-left of the page area inside the @page
      |  margin, which is what an absolutely positioned element measures from
      |  when nothing above it is positioned.
      |
      |    printable width          289mm   (297 - 4 - 4)
      |    sheet height             200mm
      |    cut line                 177.4mm  -- 60% across, NOT the middle
      |    dry seal panel           156.4mm .. 198.4mm, top 139mm, ~16mm tall
      |    both OR blocks           top 159mm
      |    both footers             top 187mm
      |
      |  The bottom strip is positioned absolutely rather than left in flow.
      |  In flow its height depends on how much text sits above it, and the
      |  seal panel -- which cannot be in flow, because it straddles the cut
      |  line and a table cell cannot cross a column -- would then land on top
      |  of the OR block whenever the content above ran long. Pinning both
      |  makes the 3mm gap between them fixed.
      |
      |  To move anything, change its top value in the pinned block at the
      |  foot of this file. Keep the seal panel's left at 177.4mm minus half
      |  its width so it stays centred on the cut line.
    */
    @page { size: 297mm 210mm; margin: 4mm; }

    /* DejaVu ships with Dompdf and carries ñ, which the core fonts do not. */
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color:#000; margin:0; }

    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: bottom; }

    /* table-layout:fixed holds the split at 60/40. Without it Dompdf sizes the
       columns by content, and a long programme name widens the credential
       side at the return slip's expense -- moving the cut line, and with it
       every measurement above. */
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

    /* The two headings are sans on the printed form, not the serif used for
       the letterhead above them. */
    .office { font-size:11pt; text-align:center; letter-spacing:.4pt; margin-top:5mm; }
    .title  { font-size:15pt; text-align:center; letter-spacing:.6pt; margin-top:7mm; }

    .concern { font-size:9.5pt; font-weight:bold; }

    /*
      | The body of the credential is set in a script face on the printed form.
      | Dompdf carries no script font, so italic serif stands in -- it keeps the
      | contrast against the upright entries without needing an embedded font.
    */
    .lead  { font-family:"Times New Roman",serif; font-style:italic; font-size:10pt; }
    .plain { font-family:"Times New Roman",serif; font-style:normal; font-size:10pt; }

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
        font-size: 10pt;
        color: #000;
        text-align: center;
        padding: 0 1mm .3mm;
    }

    /* Rows of the certifying sentence. */
    .sentence td { font-family:"Times New Roman",serif; font-style:italic; font-size:10pt;
                   padding-bottom:.3mm; }
    .sentence .val { font-style:normal; }

    .sig-line { border-bottom:.7pt solid #000; }
    .sig-cap  { font-size:8.5pt; padding-top:1mm; }
    .sig-name { font-family:"Times New Roman",serif; font-size:10pt; text-align:center;
                padding-bottom:.4mm; }

    .sealbox { border:.8pt dashed #000; text-align:center;
               font-size:8pt; line-height:1.5; padding:1.8mm 1mm; background:#fff; }

    /* Receipt figures are serif on the printed form. */
    .receipt td   { font-family:"Times New Roman",serif; font-size:9pt; padding:.5mm 0; }
    .receipt .val { text-align:left; padding-left:1mm; }

    .foot td { font-size:8pt; }
    .mono { font-family:"DejaVu Sans Mono", monospace; }

    .rs-title { font-size:9.5pt; font-weight:bold; }
    .rs-sub   { font-size:6.5pt; }
    .rs-body  { font-size:9pt; }
    .rs-head  { font-size:9pt; font-weight:bold; }
    .rs-line  { border-bottom:.7pt solid #000; height:4.5mm; }
    .rs-cap   { font-size:8pt; font-style:italic; text-align:center; }
    .tick     { border:.8pt solid #000; width:3.2mm; height:3.2mm; }
</style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════════════════
     The sheet. Only the upper, flowing part of each half lives here; the
     bottom strip is pinned below so it cannot collide with the seal panel.
     ═══════════════════════════════════════════════════════════════════ --}}
<table class="sheet">
<tr>

    {{-- ─────────────── LEFT · the credential ─────────────── --}}
    <td class="half cut" style="width:60%">

        @include('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23'])

        <div class="office">OFFICE OF THE REGISTRAR</div>
        <div class="title">TRANSFER CREDENTIAL</div>

        {{-- Date of issue. Sits right of centre on the printed form. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:44%"></td>
                <td class="val" style="width:32%">{{ $d('issued_on') }}</td>
                <td style="width:24%"></td>
            </tr>
        </table>

        <div class="concern" style="margin-top:7mm">To Whom It May Concern:</div>

        {{--
            The certifying sentence runs over three lines on the printed form,
            and the line breaks are part of its look:

              This is to certify that MR. / MS. ______ of ______,
              a __ year student | graduate of ______ and whose signature appears below
              has been granted Transfer Credential effective today.
        --}}
        <table class="sentence" style="margin-top:4mm">
            <tr>
                <td style="width:6mm"></td>
                <td style="width:44mm">This is to certify that <span class="plain">MR. / MS.</span></td>
                <td class="val">{{ $v('full_name') }}</td>
                <td style="width:6mm; text-align:center">of</td>
                <td class="val" style="width:46mm">{{ $v('address') }}</td>
                <td style="width:2mm">,</td>
            </tr>
        </table>

        <table class="sentence" style="margin-top:3mm">
            <tr>
                <td style="width:4mm">a</td>
                <td class="val" style="width:16mm">{{ $v('year_level') }}</td>
                <td style="width:34mm; text-align:center">{{ $standingText }}</td>
                <td class="val">{{ $v('program') }}</td>
                <td style="width:52mm; padding-left:2mm">and whose signature appears below</td>
            </tr>
        </table>

        <div class="lead" style="margin-top:2mm">
            has been granted Transfer Credential effective today.
        </div>

        <div class="lead" style="margin-top:5mm">
            <span class="plain">His/Her</span> Transcript of Records will be forwarded only upon
            receipt of the return slip.
        </div>

        {{-- The student signs in ink over their printed name. On the blank form
             this rule sits directly beneath the sentence above it. --}}
        <table style="margin-top:1mm">
            <tr><td class="sig-name" style="width:66mm">{{ $v('full_name') }}</td><td></td></tr>
            <tr><td class="sig-line"></td><td></td></tr>
            <tr><td class="sig-cap">Signature of Student over Printed Name</td><td></td></tr>
        </table>

        {{--
            The Registrar signs on the right. The rule stops at 88% of the
            column rather than running to the cut line: past that point it
            would pass behind the seal panel, which is pinned across the line
            at the same height.
        --}}
        <table style="margin-top:9mm">
            <tr>
                <td style="width:62%"></td>
                <td class="sig-line" style="width:26%"></td>
                <td style="width:12%"></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-family:'Times New Roman',serif; font-size:10pt;
                           text-align:center; padding-top:.8mm">{{ $registrar }}</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-size:8pt; text-align:center">University Registrar</td>
                <td></td>
            </tr>
        </table>
    </td>

    {{-- ─────────────── RIGHT · the return slip ─────────────── --}}
    <td class="half" style="width:40%">

        @include('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23-A'])

        <div style="margin-top:3.5mm">
            <div class="rs-title">Return Slip</div>
            <div class="rs-sub">(to be filled by requesting school)</div>
        </div>

        {{-- School and address rules are indented from the left on the printed
             form, not centred -- the left margin carries no text beside them. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:8%"></td>
                <td class="rs-line"></td>
                <td style="width:4%"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Name of School</td>
                <td></td>
            </tr>
            <tr><td colspan="3" style="height:2.5mm"></td></tr>
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

        <table style="margin-top:4mm">
            <tr>
                <td style="width:42%"></td>
                <td class="rs-line"></td>
                <td style="width:4%"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Date</td>
                <td></td>
            </tr>
        </table>

        <div class="rs-body" style="margin-top:6mm; line-height:1.4">
            <div class="rs-head">The Registrar</div>
            <div>{{ config('celeste.institution.name', 'Partido State University') }}</div>
            <div>Goa, {{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
        </div>

        <div class="rs-head" style="margin-top:5mm">Madam:</div>

        <table style="margin-top:2.5mm">
            <tr>
                <td class="rs-body" style="line-height:1.5" colspan="3">
                    This is to acknowledge receipt of the Transfer Credential granted
                </td>
            </tr>
            <tr>
                <td class="rs-body" style="width:20mm">to Mr. /Ms.</td>
                <td class="rs-line"></td>
                <td class="rs-body" style="width:2mm">.</td>
            </tr>
        </table>

        {{-- Signature rules start at 20% so they clear the seal panel, which
             reaches 198.4mm across -- just inside this column's left edge. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:20%"></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Signature over Printed Name</td>
            </tr>
            <tr><td colspan="2" style="height:3.5mm"></td></tr>
            <tr>
                <td></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Position/Designation</td>
            </tr>
        </table>
    </td>

</tr>
</table>


{{-- ═══════════════════════════════════════════════════════════════════
     PINNED BOTTOM STRIP

     Everything below sits at a fixed height, so the seal panel and the OR
     blocks keep their 3mm separation no matter how the text above reflows.
     Heights are the ones measured off the blank form; see the note at the
     top of the stylesheet before changing them.
     ═══════════════════════════════════════════════════════════════════ --}}

{{-- Dry seal panel · centred on the cut line at 177.4mm, above both OR blocks.
     The white background is not decoration: without it the dashed cut line
     runs straight through the panel instead of stopping at its edges. --}}
<div style="position:absolute; left:156.4mm; top:139mm; width:42mm">
    <div class="sealbox">Documentary<br>Stamp<br>And Dry Seal Here</div>
</div>

{{-- Receipt figures, credential side. --}}
<div style="position:absolute; left:9mm; top:159mm; width:46mm">
    <table class="receipt">
        <tr>
            <td style="width:18mm">OR:</td>
            <td class="val">{{ $v('or_no') }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="val">{{ $d('or_date') }}</td>
        </tr>
        <tr>
            <td>Cert. Fee:</td>
            <td class="val">Php {{ $v('cert_fee') }}</td>
        </tr>
    </table>
</div>

{{-- Verification block. The one addition to the official layout: this strip
     is blank on the printed form, so nothing the University approved is
     displaced by it. Kept clear of the seal panel, which starts at 156.4mm. --}}
<div style="position:absolute; left:100mm; top:152mm; width:34mm; text-align:center">
    @if (!empty($qr))
        <img src="{{ $qr }}" style="width:20mm;height:20mm">
    @else
        <div style="width:20mm;height:20mm;border:.5pt dashed #999;
                    font-size:5pt;color:#999;margin:0 auto">
            <div style="padding-top:7.5mm">QR</div>
        </div>
    @endif
    <div class="mono" style="font-size:6.2pt; padding-top:.7mm">
        {{ $certificate->serial_number }}
    </div>
    <div style="font-size:5.8pt; color:#444">Scan to verify</div>
</div>

{{-- Receipt figures, return slip side. All blank: this is the receiving
     school's section. --}}
<div style="position:absolute; left:182mm; top:159mm; width:58mm">
    <table class="receipt">
        <tr><td style="width:24mm">OR No.:</td><td class="val"></td></tr>
        <tr><td>Date:</td><td class="val"></td></tr>
        <tr><td>Cert. Fee:</td><td class="val">Php</td></tr>
        <tr><td>T.C.</td><td class="val"></td></tr>
        <tr><td>Course:</td><td class="val"></td></tr>
        <tr><td>Year Graduated:</td><td class="val"></td></tr>
    </table>
</div>

{{-- How the receiving school wishes the transcript returned. --}}
<div style="position:absolute; left:243mm; top:176mm; width:48mm">
    <table>
        <tr>
            <td class="tick"></td>
            <td style="font-size:8pt; font-style:italic; padding-left:1.8mm">
                Please entrust to the bearer.
            </td>
        </tr>
    </table>
</div>

</body>
</html>