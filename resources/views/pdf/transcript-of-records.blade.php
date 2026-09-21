@php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    $isNew = ($p['admission_type'] ?? 'new') === 'new';

    $tickNew = $isNew ? '×' : ' ';
    $tickTr  = $isNew ? ' ' : '×';

    $subjects = collect($p['grades'] ?? []);
    $grouped  = $subjects->groupBy(fn ($r) => trim(
        ($r['term'] ?? '') ?: (($r['semester'] ?? '') . ' ' . ($r['academic_year'] ?? ''))
    ));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $certificate->serial_number }}</title>
<style>
    @page { size: 216mm 330mm; margin: 7mm 7mm 5mm 7mm; }

    body { font-family: "DejaVu Sans", sans-serif; font-size: 6.8pt; color:#000; margin:0; }

    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; }

    .bx { border: .7pt solid #000; }
    .bx > tbody > tr > td { padding: .7mm 1.4mm; }

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

    .subj-head td { font-size:6.8pt; padding:.8mm .6mm; border:0; }
    .subj td { font-size:6.8pt; padding:.35mm 1.2mm; height:4.2mm; border:0; }
    .subj .close { text-align:center; padding:2mm 0; }

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

{{-- ══════════════════════ PAGE 1 ══════════════════════ --}}
@include('pdf.partials.tor-header')
@include('pdf.partials.tor-identity', ['p' => $p])

{{-- PERSONAL INFORMATION --}}
<table class="bx" style="margin-top:2mm">
    <tr><td colspan="2" style="padding:0"><span class="bar">PERSONAL INFORMATION</span></td></tr>
    @foreach ([
        'Address'     => $v('address'),
        'Gender'      => $v('gender'),
        'Nationality' => $v('nationality'),
        'Birthdate'   => $d('birth_date'),
        'Birthplace'  => $v('birthplace'),
    ] as $label => $value)
        <tr>
            <td class="lbl" style="width:26mm">{{ $label }}:</td>
            <td class="plain">{{ $value }}</td>
        </tr>
    @endforeach
</table>

<table class="bx" style="margin-top:2mm">
    <tr>
        {{-- Left column --}}
        <td style="width:52%; border-right:.7pt solid #000; padding:0">
            <table>
                <tr><td colspan="2" style="padding:0"><span class="bar">ADMISSION DATA</span></td></tr>

                <tr>
                    <td colspan="2" class="lbl" style="padding:1mm 1.4mm .3mm">
                        A. <span class="tick">{{ $tickNew }}</span> NEW
                    </td>
                </tr>
                @foreach ([
                    'School'         => $isNew ? $v('adm_new_school') : '',
                    'Address'        => $isNew ? $v('adm_new_address') : '',
                    'Course'         => $isNew ? $v('adm_new_course') : '',
                    'Year Graduated' => $isNew ? $v('adm_new_year_graduated') : '',
                ] as $label => $value)
                    <tr>
                        <td class="lbl" style="width:26mm; padding:.5mm 1.4mm .5mm 5mm">{{ $label }}:</td>
                        <td class="plain" style="padding:.5mm 1.4mm">{{ $value }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" class="lbl" style="padding:2.5mm 1.4mm .3mm 5mm">
                        <span class="tick">{{ $tickTr }}</span> TRANSFEREE
                    </td>
                </tr>
                @foreach ([
                    'School'               => $isNew ? '' : $v('adm_tr_school'),
                    'Address'              => $isNew ? '' : $v('adm_tr_address'),
                    'Course'               => $isNew ? '' : $v('adm_tr_course'),
                    'Year Graduated'       => $isNew ? '' : $v('adm_tr_year_graduated'),
                    'Admission Credential' => $isNew ? '' : $v('adm_tr_credential'),
                ] as $label => $value)
                    <tr>
                        <td class="lbl" style="width:26mm; padding:.5mm 1.4mm .5mm 5mm">{{ $label }}:</td>
                        <td class="plain" style="padding:.5mm 1.4mm">{{ $value }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td class="lbl" style="padding:2.5mm 1.4mm 1.5mm">B.&nbsp; Date of Admission:</td>
                    <td class="plain" style="padding:2.5mm 1.4mm 1.5mm">{{ $d('date_admitted') }}</td>
                </tr>
            </table>
        </td>

        {{-- Right column, split by a horizontal rule --}}
        <td style="width:48%; padding:0">
            <table>
                <tr>
                    <td style="padding:0">
                        <table>
                            <tr><td colspan="2" style="padding:0"><span class="bar">GRADUATION DATA</span></td></tr>
                            @foreach ([
                                'Date Conferred'       => $d('date_conferred'),
                                'Board Resolution No.' => $v('board_resolution_no'),
                                'Date'                 => $d('board_resolution_date'),
                            ] as $label => $value)
                                <tr>
                                    <td class="lbl" style="width:30mm; padding:.5mm 1.4mm .5mm 5mm">{{ $label }}:</td>
                                    <td class="plain" style="padding:.5mm 1.4mm">{{ $value }}</td>
                                </tr>
                            @endforeach
                            <tr><td colspan="2" style="height:6mm"></td></tr>
                            <tr>
                                <td class="lbl" style="padding:.5mm 1.4mm 2mm 5mm">Awards:</td>
                                <td class="plain" style="padding:.5mm 1.4mm 2mm">{{ $v('awards') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:.7pt solid #000; padding:0">
                        <span class="bar">NSTP SERIAL NO.:</span>
                        <div class="plain" style="text-align:center; padding:6mm 1.4mm 5mm">
                            {{ $v('nstp_serial_no') }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- PROGRAM ACCREDITATION STATUS --}}
<table class="bx" style="margin-top:2mm">
    <tr><td class="lbl">PROGRAM ACCREDITATION STATUS:</td></tr>
    <tr><td style="height:8mm">{{ $v('program_accreditation') }}</td></tr>
</table>

{{-- GRANTED TRANSFER CREDENTIALS · receipt --}}
<table style="margin-top:2mm">
    <tr>
        <td style="width:72%; padding-right:2.5mm">
            <table class="bx">
                <tr><td class="lbl">GRANTED TRANSFER CREDENTIALS :</td></tr>
                <tr><td style="height:9mm">{{ $v('granted_transfer_credentials') }}</td></tr>
            </table>
        </td>
        <td style="width:28%">
            <table class="bx">
                @foreach ([
                    'OR NO.' => $v('or_no'),
                    'DATE'   => $d('or_date'),
                    'AMOUNT' => filled($p['cert_fee'] ?? $p['or_amount'] ?? null)
                                ? 'Php ' . ($p['cert_fee'] ?? $p['or_amount']) : '',
                ] as $label => $value)
                    <tr>
                        <td class="lbl" style="width:15mm">{{ $label }}:</td>
                        <td class="plain">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

{{-- REMARKS --}}
<table class="bx" style="margin-top:2mm">
    <tr><td class="lbl">REMARKS:</td></tr>
    <tr><td style="height:11mm">{{ $v('remarks') }}</td></tr>
</table>

@include('pdf.partials.tor-footer', [
    'p' => $p, 'page' => 1, 'pages' => 2,
    'certificate' => $certificate, 'qr' => $qr ?? null,
])

<div class="brk"></div>

{{-- ══════════════════════ PAGE 2 ══════════════════════ --}}
@include('pdf.partials.tor-header')
@include('pdf.partials.tor-identity', ['p' => $p])

{{-- Column labels above open space. No surrounding box: the printed form
     leaves this area blank for the entries. --}}
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
    @foreach ($grouped as $term => $list)
        @if (filled($term))
            <tr><td colspan="5">{{ mb_strtoupper($term) }}</td></tr>
        @endif
        @foreach ($list as $s)
            <tr>
                <td style="width:22%">{{ $s['code'] ?? '' }}</td>
                <td style="width:46%">{{ $s['title'] ?? '' }}</td>
                <td style="width:11%; text-align:center">{{ $s['grade'] ?? '' }}</td>
                <td style="width:11%; text-align:center">{{ $s['removal'] ?? '' }}</td>
                <td style="width:10%; text-align:center">{{ filled($s['units'] ?? null) ? number_format((float) $s['units'], 1) : '' }}</td>
            </tr>
        @endforeach
    @endforeach

    <tr>
        <td colspan="5" class="close">
            psupsupsupsupsupsupsupsupsupsupsupsu&nbsp;&nbsp;transcript closed&nbsp;&nbsp;psupsupsupsupsupsupsupsupsupsupsupsu
        </td>
    </tr>
</table>
</td></tr>
</table>

@include('pdf.partials.tor-footer', [
    'p' => $p, 'page' => 2, 'pages' => 2,
    'certificate' => $certificate, 'qrPath' => $qrPath ?? null,
])

</body>
</html>
