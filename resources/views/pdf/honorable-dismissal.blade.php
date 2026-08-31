{{--
    Transfer Credential · PSU-F-URO-23
    ---------------------------------------------------------------------------
    One landscape page in two halves, divided by the cut line:

      left  · PSU-F-URO-23    the credential the University issues
      right · PSU-F-URO-23-A  the return slip the receiving school completes

    The right half is deliberately blank. It is filled in by the school the
    student transfers to, cut off, and returned — which is why the credential
    says the Transcript of Records is forwarded only upon its receipt.

    ---------------------------------------------------------------------------
    Why the sentence is built from tables
    ---------------------------------------------------------------------------
    An earlier version set the filled values as inline-block spans with widths
    and overflow rules. That is correct CSS and Dompdf ignores most of it:
    inline-block is only partially implemented, so widths did not apply and a
    long name ran across the words beside it.

    Tables are what Dompdf renders reliably — the same reason the transcript is
    built from them. Each line of the certifying sentence is a table row: fixed
    cells for the static words, ruled cells for the values. A value now sits on
    its own rule at a width the page controls, rather than pushing its
    neighbours aside.

    Values come from $certificate->payload, the snapshot taken at issuance,
    which is what the fingerprint covers.
--}}
@php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    // The form reads "a ___ year student | graduate of". Where the record says
    // which applies, print only that word; otherwise print both, as the blank
    // form does, for the office to strike one through.
    $standing = $p['standing'] ?? null;

    $standingText = match ($standing) {
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
    @page { size: 297mm 210mm; margin: 6mm; }

    /* DejaVu ships with Dompdf and carries ñ, which the core fonts do not. */
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color:#000; margin:0; }

    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: bottom; }

    .sheet { border: .8pt solid #000; height: 196mm; }
    .sheet > tbody > tr > td { vertical-align: top; }

    /* The cut line. The printed form marks it with scissors; a dashed rule
       reads the same and survives photocopying better than a glyph. */
    .cut  { border-right: .8pt dashed #000; }
    .half { padding: 4mm 5mm; }

    .h-rep  { font-family:"Times New Roman",serif; font-size:8pt; }
    .h-uni  { font-family:"Times New Roman",serif; font-size:12.5pt; color:#1F4E79; letter-spacing:.2pt; }
    .h-camp { font-family:"Times New Roman",serif; font-size:8.5pt; }
    .formno { font-size:7pt; text-align:right; }
    .rule   { border-bottom:.8pt solid #1F4E79; margin-top:1mm; }

    .office { font-family:"Times New Roman",serif; font-size:11pt; text-align:center;
              letter-spacing:.6pt; margin-top:5mm; }
    .title  { font-family:"Times New Roman",serif; font-size:15pt; text-align:center;
              letter-spacing:1pt; margin-top:6mm; }

    /* The body of the credential is set in a script face on the printed form.
       Dompdf carries no script font, so italic serif stands in. */
    .lead  { font-family:"Times New Roman",serif; font-style:italic; font-size:10pt; }
    .plain { font-family:"Times New Roman",serif; font-style:normal; font-size:10pt; }

    /*
      | Filled values match the text they sit among — same family, same size.
      | A value printed a half-point smaller than the sentence around it reads
      | as a different document pasted in, which is what the earlier version
      | did at 9.5pt inside a 10pt sentence.
      |
      | Upright rather than italic: the form's own text is set in a script
      | face, but an entry written onto a ruled line is upright, and a name in
      | italic script is harder to read at speed. Change font-style here if the
      | office wants it to match the printed wording exactly.
      |
      | Applied to table cells rather than inline spans, because Dompdf honours
      | a width on a <td> and largely ignores one on an inline-block.
    */
    .val {
        border-bottom: .7pt solid #000;
        font-family: "Times New Roman", serif;
        font-style: normal;
        font-size: 10pt;           /* matches .lead and .sentence td */
        color: #000;
        text-align: center;
        padding: 0 1mm .4mm;
    }

    /* Rows of the certifying sentence. */
    .sentence td { font-family:"Times New Roman",serif; font-style:italic; font-size:10pt;
                   padding-bottom:.4mm; }
    .sentence .val { font-style:normal; }

    .sig-line { border-bottom:.7pt solid #000; }
    .sig-cap  { font-size:8pt; padding-top:.8mm; }
    .sig-name { font-family:"Times New Roman",serif; font-size:10pt; text-align:center;
                padding-bottom:.5mm; }

    .sealbox { border:.7pt dashed #000; text-align:center;
               font-size:7.5pt; line-height:1.5; padding:2mm 1mm; }

    /* The receipt block is labelled in the sans face at 8pt, so its values
       follow it rather than the 10pt of the certifying sentence. Matching the
       nearest label is what keeps each block internally consistent. */
    .receipt td   { font-size:8pt; padding:.6mm 0; }
    .receipt .val { font-family:"DejaVu Sans",sans-serif; font-size:8pt;
                    text-align:left; padding-left:1mm; }

    .foot td { font-size:7.5pt; }
    .mono { font-family:"DejaVu Sans Mono", monospace; }

    .rs-title { font-size:9pt; }
    .rs-sub   { font-size:6.5pt; }
    .rs-line  { border-bottom:.7pt solid #000; height:5mm; }
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

        {{-- Date of issue, on its rule. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:30%"></td>
                <td class="val" style="width:44%">{{ $d('issued_on') }}</td>
                <td style="width:26%"></td>
            </tr>
        </table>

        <div style="margin-top:6mm; font-size:9pt">To Whom It May Concern:</div>

        {{-- Line one: name and address. --}}
        <table class="sentence" style="margin-top:3mm">
            <tr>
                <td style="width:6mm"></td>
                <td style="width:44mm">This is to certify that <span class="plain">MR. / MS.</span></td>
                <td class="val">{{ $v('full_name') }}</td>
                <td style="width:6mm; text-align:center">of</td>
                <td class="val" style="width:44mm">{{ $v('address') }}</td>
                <td style="width:2mm">,</td>
            </tr>
        </table>

        {{-- Line two: year level, standing, course. --}}
        <table class="sentence" style="margin-top:2.5mm">
            <tr>
                <td style="width:4mm">a</td>
                <td class="val" style="width:16mm">{{ $v('year_level') }}</td>
                <td style="width:36mm; text-align:center">{{ $standingText }}</td>
                <td class="val">{{ $v('program') }}</td>
            </tr>
        </table>

        <div class="lead" style="margin-top:2.5mm">
            and whose signature appears below has been granted Transfer Credential
            effective today.
        </div>

        <div class="lead" style="margin-top:3mm">
            <span class="plain">His/Her</span> Transcript of Records will be forwarded only upon
            receipt of the return slip.
        </div>

        {{-- The student signs in ink over their printed name. --}}
        <table style="margin-top:5mm">
            <tr><td class="sig-name" style="width:62mm">{{ $v('full_name') }}</td><td></td></tr>
            <tr><td class="sig-line"></td><td></td></tr>
            <tr><td class="sig-cap">Signature of Student over Printed Name</td><td></td></tr>
        </table>

        {{-- Verification block and the Registrar's signature. --}}
        <table style="margin-top:7mm">
            <tr>
                <td style="width:32mm; vertical-align:top">
                    <table>
                        <tr>
                            <td style="text-align:center; padding-bottom:1mm">
                                @if (!empty($qr))
                                    <img src="{{ $qr }}" style="width:24mm;height:24mm">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="mono" style="text-align:center; font-size:6.6pt">
                                {{ $certificate->serial_number }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:center; font-size:5.8pt; color:#444">Scan to verify</td>
                        </tr>
                    </table>
                </td>

                <td style="vertical-align:bottom; padding-left:4mm">
                    <table>
                        <tr>
                            <td style="width:12mm"></td>
                            <td class="sig-line" style="width:52mm"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="font-family:'Times New Roman',serif; font-size:10pt;
                                       text-align:center; padding-top:.6mm">{{ $registrar }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="font-size:7.5pt; text-align:center">University Registrar</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="padding-top:4mm">
                                <table>
                                    <tr>
                                        <td style="width:9mm"></td>
                                        <td class="sealbox" style="width:34mm">
                                            Documentary<br>Stamp<br>And Dry Seal Here
                                        </td>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Receipt figures, bottom left as on the form. --}}
        <table class="receipt" style="width:52mm; margin-top:5mm">
            <tr>
                <td style="width:17mm">OR:</td>
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

        <table class="foot" style="margin-top:4mm">
            <tr>
                <td style="width:44%">Effectivity Date: January 2, 2025</td>
                <td style="width:28%; text-align:center">Rev. No: 03</td>
                <td style="width:28%; text-align:right">Page 1 of 1</td>
            </tr>
        </table>
    </td>

    {{-- ═══════════════ RIGHT · the return slip ═══════════════ --}}
    <td class="half" style="width:50%">

        @include('pdf.partials.tc-header', ['formNo' => 'PSU-F-URO-23-A'])

        <div style="margin-top:4mm">
            <div class="rs-title">Return Slip</div>
            <div class="rs-sub">(to be filled by requesting school)</div>
        </div>

        <table style="margin-top:9mm; width:88%; margin-left:auto; margin-right:auto">
            <tr><td class="rs-line"></td></tr>
            <tr><td class="rs-cap">Name of School</td></tr>
            <tr><td style="height:4mm"></td></tr>
            <tr><td class="rs-line"></td></tr>
            <tr><td class="rs-cap">Address</td></tr>
        </table>

        <table style="margin-top:4mm">
            <tr>
                <td style="width:48%"></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Date</td>
            </tr>
        </table>

        <div style="margin-top:7mm; font-size:8.5pt; line-height:1.5">
            <div>The Registrar</div>
            <div>{{ config('celeste.institution.name', 'Partido State University') }}</div>
            <div>Goa, {{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
        </div>

        <div style="margin-top:6mm; font-size:8.5pt">Madam:</div>

        <table style="margin-top:2mm">
            <tr>
                <td style="font-size:8.5pt; line-height:1.7" colspan="2">
                    This is to acknowledge receipt of the Transfer Credential granted
                </td>
            </tr>
            <tr>
                <td style="width:22mm; font-size:8.5pt">to Mr. /Ms.</td>
                <td class="rs-line"></td>
            </tr>
        </table>

        <table style="margin-top:12mm">
            <tr>
                <td style="width:26%"></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Signature over Printed Name</td>
            </tr>
            <tr><td colspan="2" style="height:6mm"></td></tr>
            <tr>
                <td></td>
                <td class="rs-line"></td>
            </tr>
            <tr>
                <td></td>
                <td class="rs-cap">Position/Designation</td>
            </tr>
        </table>

        {{-- The school records what it received, and how it wishes the
             transcript returned. --}}
        <table style="margin-top:5mm">
            <tr>
                <td style="width:52%; vertical-align:top">
                    <table class="receipt">
                        <tr><td style="width:22mm">OR No.:</td><td class="val"></td></tr>
                        <tr><td>Date:</td><td class="val"></td></tr>
                        <tr><td>Cert. Fee:</td><td class="val">Php</td></tr>
                        <tr><td>T.C.</td><td class="val"></td></tr>
                        <tr><td>Course:</td><td class="val"></td></tr>
                        <tr><td>Year Graduated:</td><td class="val"></td></tr>
                    </table>
                </td>
                <td style="width:48%; vertical-align:bottom">
                    <table>
                        <tr>
                            <td class="tick"></td>
                            <td style="font-size:8pt; font-style:italic; padding-left:1.5mm">
                                Please entrust to the bearer.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="foot" style="margin-top:4mm">
            <tr>
                <td style="width:44%">Effectivity Date: January 2, 2025</td>
                <td style="width:28%; text-align:center">Rev. No: 03</td>
                <td style="width:28%; text-align:right">Page 1 of 1</td>
            </tr>
        </table>
    </td>

</tr>
</table>

</body>
</html>
