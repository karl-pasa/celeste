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

    {{-- ═══════════════ LEFT · the credential ═══════════════ --}}
    <td class="half cut" style="width:50%">

        @include('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23'])

        <div class="office">OFFICE OF THE REGISTRAR</div>
        <div class="title">TRANSFER CREDENTIAL</div>

        {{-- Date of issue. Sits right of centre on the printed form. --}}
        <table style="margin-top:6mm">
            <tr>
                <td style="width:46%"></td>
                <td class="val" style="width:34%">{{ $d('issued_on') }}</td>
                <td style="width:20%"></td>
            </tr>
        </table>

        <div style="margin-top:6mm; font-size:9pt">To Whom It May Concern:</div>

        {{--
            The certifying sentence runs over three lines on the printed form,
            and the line breaks are part of its look:

              This is to certify that MR. / MS. ______ of ______,
              a __ year student | graduate of ______ and whose signature appears below
              has been granted Transfer Credential effective today.

            The trailing phrase belongs on line two, not on a line of its own.
        --}}
        <table class="sentence" style="margin-top:3.5mm">
            <tr>
                <td style="width:5mm"></td>
                <td style="width:42mm">This is to certify that <span class="plain">MR. / MS.</span></td>
                <td class="val">{{ $v('full_name') }}</td>
                <td style="width:5mm; text-align:center">of</td>
                <td class="val" style="width:40mm">{{ $v('address') }}</td>
                <td style="width:2mm">,</td>
            </tr>
        </table>

        <table class="sentence" style="margin-top:2.5mm">
            <tr>
                <td style="width:4mm">a</td>
                <td class="val" style="width:14mm">{{ $v('year_level') }}</td>
                <td style="width:31mm; text-align:center">{{ $standingText }}</td>
                <td class="val">{{ $v('program') }}</td>
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

        {{-- The student signs in ink over their printed name. On the blank form
             this rule sits directly beneath the sentence above it. --}}
        <table style="margin-top:1mm">
            <tr><td class="sig-name" style="width:64mm">{{ $v('full_name') }}</td><td></td></tr>
            <tr><td class="sig-line"></td><td></td></tr>
            <tr><td class="sig-cap">Signature of Student over Printed Name</td><td></td></tr>
        </table>

        {{-- The Registrar signs on the right, above the printed name. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:38%"></td>
                <td class="sig-line"></td>
            </tr>
            <tr>
                <td></td>
                <td style="font-family:'Times New Roman',serif; font-size:9.5pt;
                           text-align:center; padding-top:.6mm">{{ $registrar }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="font-size:7.5pt; text-align:center">University Registrar</td>
            </tr>
        </table>

        {{--
            Bottom band, matching the printed form left to right: receipt
            figures, then the verification block, then the dry seal panel
            against the cut line.

            The QR is the one addition to the official layout. It goes here
            because this strip is blank on the printed form, so nothing the
            University approved is displaced by it.
        --}}
        <table style="margin-top:6mm">
            <tr>
                <td style="width:38%; vertical-align:bottom">
                    <table class="receipt">
                        <tr>
                            <td style="width:16mm">OR:</td>
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
                </td>

                <td style="width:40%; vertical-align:bottom; text-align:center">
                    {{-- $qr is a data URI supplied by the generator. A dashed
                         placeholder is drawn if it is absent, so the layout can
                         be checked before it is wired. --}}
                    @if (!empty($qr))
                        <img src="{{ $qr }}" style="width:19mm;height:19mm">
                    @else
                        <div style="width:19mm;height:19mm;border:.5pt dashed #999;
                                    font-size:5pt;color:#999;margin:0 auto">
                            <div style="padding-top:7mm">QR</div>
                        </div>
                    @endif
                    <div class="mono" style="font-size:6.2pt; padding-top:.6mm">
                        {{ $certificate->serial_number }}
                    </div>
                    <div style="font-size:5.6pt; color:#444">Scan to verify</div>
                </td>

                {{-- The dry seal panel is not here: it straddles the cut line,
                     and a table cell cannot cross into the next column. It is
                     positioned absolutely at the foot of this file. --}}
                <td style="width:22%"></td>
            </tr>
        </table>

    </td>

    {{-- ═══════════════ RIGHT · the return slip ═══════════════ --}}
    <td class="half" style="width:50%">

        @include('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23-A'])

        <div style="margin-top:3mm">
            <div class="rs-title">Return Slip</div>
            <div class="rs-sub">(to be filled by requesting school)</div>
        </div>

        {{-- School and address rules are indented from the left on the printed
             form, not centred -- the left margin carries no text beside them. --}}
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
            <div>{{ config('celeste.institution.name', 'Partido State University') }}</div>
            <div>Goa, {{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
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

        {{-- The receiving school records what it received, and how it wishes
             the transcript returned. All blank: this is their section. --}}
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

{{--
    The dry seal panel, centred on the cut line exactly as on the printed form.

    Absolute rather than in-flow: the panel belongs to neither half, and Dompdf
    cannot make a table cell cross a column boundary. With no positioned
    ancestor, left and top are measured from the page area inside the @page
    margin, so the sheet's horizontal centre is at 144.5mm and a 42mm panel
    starts at 123.5mm.

    A white background is needed, not decoration: without it the dashed cut
    line shows through the panel instead of stopping at its edges.

    To nudge it, change top (lower number moves it up) and keep left at
    144.5mm minus half the width.
--}}
<div style="position:absolute; left:123.5mm; top:163mm; width:42mm; background:#fff">
    <div class="sealbox">Documentary<br>Stamp<br>And Dry Seal Here</div>
</div>

</body>
</html>