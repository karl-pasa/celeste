@extends('pdf._shell')

@section('document')
    <div class="doc-title">HONORABLE DISMISSAL</div>
    <div class="doc-sub">Issued in accordance with the rules of the Commission on Higher Education</div>

    <p>TO WHOM IT MAY CONCERN:</p>

    <p class="indent">
        This is to certify that <span class="name-inline">{{ $payload['full_name'] }}</span>,
        Student Number {{ $payload['student_number'] }}, was a student of
        {{ $payload['institution'] }}, {{ $payload['campus'] }}, and is hereby granted
        HONORABLE DISMISSAL, having left the University in good standing with no pending
        academic, financial, or disciplinary obligation.
    </p>

    <table class="fields">
        <tr><td class="label">Program last enrolled in</td><td class="value">{{ $payload['program'] }}</td></tr>
        <tr><td class="label">College</td><td class="value">{{ $payload['college'] }}</td></tr>
        <tr><td class="label">Date first admitted</td><td class="value">
            {{ !empty($payload['date_admitted']) ? \Illuminate\Support\Carbon::parse($payload['date_admitted'])->format('F j, Y') : '—' }}
        </td></tr>
        <tr><td class="label">Last term attended</td><td class="value">
            {{ trim(($payload['last_semester'] ?? '') . ' ' . ($payload['academic_year'] ? 'A.Y. ' . $payload['academic_year'] : '')) ?: '—' }}
        </td></tr>
        <tr><td class="label">Purpose</td><td class="value">{{ $payload['purpose'] ?? 'Transfer to another institution' }}</td></tr>
    </table>

    <p class="indent">
        This dismissal is granted without prejudice, and the student is free to transfer to any
        institution of learning. The Transcript of Records shall be forwarded directly to the
        receiving institution upon its official request.
    </p>

    <p class="indent">
        Issued this {{ \Illuminate\Support\Carbon::parse($payload['issued_on'])->format('jS \d\a\y \o\f F, Y') }}
        at {{ $payload['campus'] }}, Philippines.
    </p>

    <table class="sig-block" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                <div class="sig-line">
                    <div class="sig-name">{{ config('celeste.officials.registrar') }}</div>
                    <div class="sig-role">University Registrar</div>
                </div>
            </td>
        </tr>
    </table>
@endsection
