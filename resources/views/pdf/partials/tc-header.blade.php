{{--
    Letterhead for the Transfer Credential. Appears on both halves, because
    each is a separate document once the sheet is cut.

    $formNo distinguishes them: PSU-F-URO-23 on the credential,
    PSU-F-URO-23-A on the return slip.
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
