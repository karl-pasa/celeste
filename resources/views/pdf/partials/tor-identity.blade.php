{{-- Student number, name, course, major, and the photograph box.
     The values print plainly, with no rule beneath them. --}}
@php $val = fn (string $k) => filled($p[$k] ?? null) ? $p[$k] : ''; @endphp
<table style="margin-top:1.2mm">
    <tr>
        <td style="width:75%">
            <table>
                @foreach ([
                    'Student Number'       => $val('student_number'),
                    'Name'                 => $val('full_name'),
                    'Course'               => $val('program'),
                    'Major/Specialization' => $val('major'),
                ] as $label => $value)
                    <tr>
                        <td class="plain" style="width:32mm; padding:.7mm 0">{{ $label }}:</td>
                        <td class="plain" style="padding:.7mm 1.2mm">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
        <td style="width:25%; padding-left:3mm">
            <div class="photo"><div style="padding-top:9mm">2 x 2<br>PHOTO</div></div>
        </td>
    </tr>
</table>
