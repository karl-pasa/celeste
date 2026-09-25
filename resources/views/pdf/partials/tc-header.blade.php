{{--
    Letterhead for the Transfer Credential.

    Appears on both halves of the sheet, because each becomes a separate
    document once the sheet is cut along the centre line.

    $formNo distinguishes them: PSU-F-URO-23 on the credential itself,
    PSU-F-URO-23-A on the return slip.

    public_path() rather than asset(): Dompdf reads the seal from disk, and a
    URL would require remote file access, which is disabled by default.
--}}
<table>
    <tr>
        <td style="width:13mm; vertical-align:middle">
            @php $seal = public_path('images/psu-seal.png'); @endphp
            @if (is_readable($seal))
                <img src="{{ $seal }}" style="width:12mm; height:12mm">
            @endif
        </td>
        <td style="text-align:center; vertical-align:middle">
            <div class="h-rep">Republic of the Philippines</div>
            <div class="h-uni">{{ mb_strtoupper(config('celeste.institution.name', 'PARTIDO STATE UNIVERSITY')) }}</div>
            <div class="h-camp">{{ config('celeste.institution.campus', 'Camarines Sur') }}</div>
        </td>
    </tr>
</table>

<div class="rule"></div>
<div class="formno">{{ $formNo }}</div>
