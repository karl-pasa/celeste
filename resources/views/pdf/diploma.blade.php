@php
    $p = $certificate->payload ?? [];

    $v = fn (string $k, string $else = '') => filled($p[$k] ?? null) ? $p[$k] : $else;

    $d = function (string $k) use ($p) {
        if (blank($p[$k] ?? null)) return '';
        try { return \Illuminate\Support\Carbon::parse($p[$k])->format('F j, Y'); }
        catch (\Throwable) { return (string) $p[$k]; }
    };

    /*
      The conferral date prints as "this 8th day of June in the year of our
      Lord, 2026" -- three separate pieces, so it is split here rather than
      formatted into one string. Carbon's "jS" gives 1st/2nd/3rd/8th, and the
      ordinal suffix is raised in the original, so it is emitted separately.
    */
    $conferred = null;
    if (filled($p['date_graduated'] ?? null)) {
        try { $conferred = \Illuminate\Support\Carbon::parse($p['date_graduated']); }
        catch (\Throwable) { $conferred = null; }
    }

    $dayNumber = $conferred ? $conferred->format('j') : '';
    $daySuffix = $conferred ? substr($conferred->format('jS'), strlen($dayNumber)) : '';
    $monthName = $conferred ? $conferred->format('F') : '';
    $yearName  = $conferred ? $conferred->format('Y') : '';

    $registrar  = config('celeste.officials.registrar', '');
    $registrarT = config('celeste.officials.registrar_title', 'University Registrar');
    $president  = config('celeste.officials.president', '');
    $presidentT = config('celeste.officials.president_title', 'SUC President III');

    // Assets are optional. A missing seal or signature leaves a gap rather
    // than breaking the render, which matters when the office is still
    // collecting scans.
    $asset = function (string $relative) {
        $path = public_path($relative);
        return is_file($path) ? $path : null;
    };

    $border     = $asset('images/diploma-border.png');
    $seal       = $asset('images/psu-seal.png');
    $signRegist = $asset('images/sig-registrar.png');
    $signPres   = $asset('images/sig-president.png');
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
      |  Measured off the photographed diploma, converted to millimetres on
      |  A4 portrait (210 x 297mm). Every figure below is from the top-left
      |  of the page area inside the @page margin.
      |
      |  @page margin is 0 here, unlike the Transfer Credential. The border
      |  artwork bleeds close to the sheet edge, so there is no margin to
      |  measure from -- the numbers are absolute positions on the sheet.
      |
      |    Republic of the Philippines      30mm
      |    Partido State University         38mm
      |    Camarines Sur                    50mm
      |    TO ALL PERSONS ...               62mm
      |    Greetings                        71mm
      |    BE IT KNOWN ... block            90mm
      |    conferee name                   118mm
      |    who has fulfilled ...           136mm
      |    degree                          144mm
      |    with all the rights ...         159mm
      |    IN TESTIMONY WHEREOF            176mm
      |    the seal of ... block           183mm
      |    Given at Goa ...                200mm
      |    signature strip                 228mm
      |    seal                            222mm
      |    QR and serial                   258mm
      |    Board Resolution block          258mm
      |
      |  Everything is absolutely positioned. A diploma has no reflowing
      |  content -- each line sits at a fixed height on the sheet -- and
      |  positioning avoids a long programme name pushing the signatures off
      |  the page, which is what flow layout would do.
    */
    @page { size: 210mm 297mm; margin: 0; }

    /*
      | ─────────────────────────────────────────────────────────────────────
      |  FONTS
      | ─────────────────────────────────────────────────────────────────────
      |  Three faces carry this document:
      |
      |    Blackletter  -- Republic / Partido State University / Greetings /
      |                    the conferee's name
      |    Script       -- every italic calligraphic line
      |    Serif        -- the small-caps lines and the signature block
      |
      |  Dompdf cannot use a font that is not registered here, and it will
      |  silently fall back to its default rather than warn you. Drop the
      |  .ttf files into public/fonts/ with exactly these names. See the note
      |  at the end of this file for where to get them.
      |
      |  The signature block uses DejaVu Serif rather than Times because the
      |  registrar's surname carries an ñ, which the PDF core fonts do not.
    */
    @font-face {
        font-family: 'Blackletter';
        font-style: normal;
        font-weight: normal;
        src: url('{{ public_path("fonts/UnifrakturMaguntia-Regular.ttf") }}') format('truetype');
    }

    @font-face {
        font-family: 'Script';
        font-style: normal;
        font-weight: normal;
        src: url('{{ public_path("fonts/GreatVibes-Regular.ttf") }}') format('truetype');
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 210mm;
        height: 297mm;
        font-family: "DejaVu Serif", serif;
        color: #1a1a1a;
    }

    /* Positioned blocks all share this. Width is the full sheet so that
       text-align:center centres on the sheet, not on a content box. */
    .at {
        position: absolute;
        left: 0;
        width: 210mm;
        text-align: center;
    }

    .fraktur { font-family: 'Blackletter', serif; font-weight: normal; }
    .script  { font-family: 'Script', cursive; }
    .roman   { font-family: "Times New Roman", "DejaVu Serif", serif; }

    /* ── Border ───────────────────────────────────────────────────────── */

    .border-art {
        position: absolute;
        top: 0; left: 0;
        width: 210mm;
        height: 297mm;
    }

    /* Fallback when no border PNG is present: a double rule in the same
       position as the artwork's inner edge, so the layout still reads as a
       finished document rather than as loose text on a page. */
    .border-rule {
        position: absolute;
        top: 10mm; left: 10mm;
        width: 188mm;
        height: 275mm;
        border: 2.2mm solid #2f2f2f;
    }

    .border-rule-inner {
        position: absolute;
        top: 14mm; left: 14mm;
        width: 181mm;
        height: 268mm;
        border: .4mm solid #2f2f2f;
    }

    /* ── Type scale ───────────────────────────────────────────────────── */

    .t-republic  { font-size: 15pt; }
    .t-uni       { font-size: 32pt; }
    .t-province  { font-size: 13pt; }
    .t-presents  { font-size: 13.5pt; letter-spacing: .3pt; }
    .t-greetings { font-size: 34pt; }
    .t-beitknown { font-size: 13pt; line-height: 1.5; }
    .t-recommend { font-size: 14pt; line-height: 1.55; }
    .t-name      { font-size: 34pt; }
    .t-fulfilled { font-size: 14pt; }
    .t-degree    { font-size: 20pt; }
    .t-rights    { font-size: 14pt; line-height: 1.55; }
    .t-testimony { font-size: 13.5pt; letter-spacing: .3pt; }
    .t-seal-line { font-size: 14pt; line-height: 1.55; }
    .t-given     { font-size: 14pt; line-height: 1.6; }

    .caps { text-transform: uppercase; }

    /* The original raises the ordinal suffix on the day. vertical-align is
       one of the few inline properties Dompdf honours reliably. */
    .ordinal { font-size: 9pt; vertical-align: super; }

    /* ── Signature strip ──────────────────────────────────────────────── */

    /* A table, not floats -- Dompdf ignores float in enough cases that a
       three-column table is the only layout that holds. */
    .sig-table {
        position: absolute;
        top: 228mm;
        left: 14mm;
        width: 182mm;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sig-table td {
        vertical-align: bottom;
        text-align: center;
        font-family: "DejaVu Serif", serif;
    }

    .sig-name {
        font-size: 10.5pt;
        font-weight: bold;
        letter-spacing: .2pt;
    }

    .sig-title {
        font-size: 10pt;
        font-weight: bold;
    }

    /* The scanned signature sits above the name, overlapping the rule the
       way an ink signature does on the paper original. */
    .sig-ink {
        height: 13mm;
        margin-bottom: -2mm;
    }

    .seal-img {
        width: 42mm;
        height: 42mm;
    }

    /* ── Verification furniture ───────────────────────────────────────── */

    /*
      The QR and the serial are the parts that make this document checkable.
      They sit in the bottom-left corner, clear of the signature strip and
      inside the border, where the original carries them.
    */
    .qr-block {
        position: absolute;
        top: 256mm;
        left: 17mm;
        width: 30mm;
        text-align: center;
    }

    .qr-block img { width: 20mm; height: 20mm; }

    .qr-serial {
        margin-top: 1mm;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 6pt;
        letter-spacing: .4pt;
        color: #333;
    }

    .resolution {
        position: absolute;
        top: 258mm;
        right: 0;
        width: 196mm;
        text-align: right;
        font-family: "DejaVu Serif", serif;
        font-size: 10.5pt;
        line-height: 1.5;
    }

    .resolution em { font-style: italic; font-weight: bold; }

    /* The hash is printed small and quiet. It is not for reading -- it is
       there so a printed copy still carries what the QR encodes, for anyone
       verifying by hand through the portal. */
    .hash-line {
        position: absolute;
        top: 285mm;
        left: 0;
        width: 210mm;
        text-align: center;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 4.5pt;
        color: #8a94ad;
    }
</style>
</head>
<body>

@if ($border)
    <img class="border-art" src="{{ $border }}" alt="">
@else
    <div class="border-rule"></div>
    <div class="border-rule-inner"></div>
@endif

{{-- ── Heading ──────────────────────────────────────────────────────── --}}

<div class="at fraktur t-republic" style="top:30mm">Republic of the Philippines</div>
<div class="at fraktur t-uni"      style="top:38mm">Partido State University</div>
<div class="at roman  t-province"  style="top:50mm">Camarines Sur</div>

<div class="at roman t-presents" style="top:62mm">
    TO ALL PERSONS TO WHOM THESE PRESENTS MAY COME:
</div>

<div class="at fraktur t-greetings" style="top:71mm">Greetings</div>

{{-- ── Conferral ────────────────────────────────────────────────────── --}}

<div class="at roman t-beitknown" style="top:92mm">
    BE IT KNOWN <span class="script">that the</span> BOARD OF REGENTS <span class="script">of</span><br>
    PARTIDO STATE UNIVERSITY
</div>

<div class="at script t-recommend" style="top:105mm">
    by authority of the Republic of the Philippines and on the recommendation<br>
    of the Academic Council has conferred upon
</div>

<div class="at fraktur t-name" style="top:118mm">{{ $v('full_name') }}</div>

<div class="at script t-fulfilled" style="top:136mm">
    who has fulfilled all the requirements thereof the degree of
</div>

<div class="at script t-degree" style="top:144mm">{{ $v('program') }}</div>

@if ($v('latin_honor'))
    {{-- Honours are not on every diploma, so this line only appears when the
         record carries one. It sits between the degree and the rights
         paragraph, which is where the office writes it in. --}}
    <div class="at script" style="top:153mm;font-size:15pt">{{ $v('latin_honor') }}</div>
@endif

<div class="at script t-rights" style="top:159mm">
    with all the rights, honors and privileges as well as the obligations<br>
    and responsibilities thereunto appertaining.
</div>

{{-- ── Attestation ──────────────────────────────────────────────────── --}}

<div class="at roman t-testimony" style="top:176mm">IN TESTIMONY WHEREOF,</div>

<div class="at script t-seal-line" style="top:183mm">
    the seal of the <span class="roman" style="font-size:12.5pt">PARTIDO STATE UNIVERSITY</span> and the signatures of<br>
    the President and the University Registrar are hereunto affixed.
</div>

<div class="at script t-given" style="top:200mm">
    Given at Goa, Camarines Sur, Philippines this {{ $dayNumber }}<span class="ordinal">{{ $daySuffix }}</span> day of {{ $monthName }}<br>
    in the year of our Lord, {{ $yearName }}.
</div>

{{-- ── Signatures and seal ──────────────────────────────────────────── --}}

<table class="sig-table">
    <tr>
        <td style="width:62mm">
            @if ($signRegist)
                <img class="sig-ink" src="{{ $signRegist }}" alt="">
            @endif
        </td>
        <td style="width:58mm" rowspan="3">
            @if ($seal)
                <img class="seal-img" src="{{ $seal }}" alt="">
            @endif
        </td>
        <td style="width:62mm">
            @if ($signPres)
                <img class="sig-ink" src="{{ $signPres }}" alt="">
            @endif
        </td>
    </tr>
    <tr>
        <td class="sig-name caps">{{ $registrar }}</td>
        <td class="sig-name caps">{{ $president }}</td>
    </tr>
    <tr>
        <td class="sig-title">{{ $registrarT }}</td>
        <td class="sig-title">{{ $presidentT }}</td>
    </tr>
</table>

{{-- ── Verification ─────────────────────────────────────────────────── --}}

<div class="qr-block">
    @if (!empty($qr))
        <img src="{{ $qr }}" alt="">
    @endif
    <div class="qr-serial">{{ $certificate->serial_number }}</div>
</div>

<div class="resolution">
    Board Resolution No. <em>{{ $v('board_resolution', '—') }}</em><br>
    Date: <em>{{ $d('board_resolution_date') ?: '—' }}</em>
</div>

<div class="hash-line">SHA-256 {{ $certificate->hash ?? '' }}</div>

</body>
</html>

{{--
    ─────────────────────────────────────────────────────────────────────────
    ASSETS THIS TEMPLATE EXPECTS
    ─────────────────────────────────────────────────────────────────────────

    public/fonts/UnifrakturMaguntia-Regular.ttf
        The blackletter face. Free, SIL Open Font License, from Google Fonts.
        It is close to the hand on the original but not identical. If the
        office knows which face the printer uses, substitute that file and
        keep the filename.

    public/fonts/GreatVibes-Regular.ttf
        The calligraphic italic. Also Google Fonts, also OFL. The original
        looks closer to Edwardian Script; Great Vibes is the nearest freely
        licensed match.

    public/images/diploma-border.png
        The chevron frame, 2480 x 3508 px (A4 at 300dpi), transparent
        background. Scan a blank diploma, cut out the frame, and save it at
        that size. Until the file exists the template draws a plain double
        rule instead, so it still renders.

    public/images/psu-seal.png          500 x 500 px, transparent
    public/images/sig-registrar.png     ~900 x 300 px, transparent
    public/images/sig-president.png     ~900 x 300 px, transparent

    All five images are optional -- a missing file leaves its space empty
    rather than throwing.

    ─────────────────────────────────────────────────────────────────────────
    DOMPDF CONFIGURATION
    ─────────────────────────────────────────────────────────────────────────

    config/dompdf.php needs the public path readable:

        'chroot' => [base_path(), public_path()],

    Fonts are cached after the first render into storage/fonts/. If you swap
    a .ttf for a different one under the same name, clear that folder or
    Dompdf keeps serving the old metrics.
--}}