{{--
    Letterhead, repeated on both pages.

    public_path() rather than asset(): Dompdf reads the file from disk, and a
    URL would need remote file access, which is disabled by default.
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
        <td style="width:24mm"><div class="formno">PSU-F-URO-27</div></td>
    </tr>
</table>

<div style="text-align:center">
    <div class="h-off">OFFICE OF THE UNIVERSITY REGISTRAR</div>
    <div class="h-tel">
        Tel. No. (054) 871-2091 local 1170&nbsp;&nbsp;E-mail: {{ config('celeste.institution.registrar_email', 'registrar@parsu.edu.ph') }}
    </div>
</div>

<div class="h-ttl" style="margin-top:1mm">OFFICIAL TRANSCRIPT OF RECORDS</div>
