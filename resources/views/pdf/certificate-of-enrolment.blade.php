@extends('pdf._shell')

@section('document')
    <div class="doc-title">CERTIFICATE OF ENROLMENT</div>
    <div class="doc-sub">{{ $payload['semester'] ?? '' }} {{ $payload['academic_year'] ? ', A.Y. ' . $payload['academic_year'] : '' }}</div>

    <p>TO WHOM IT MAY CONCERN:</p>

    <p class="indent">
        This is to certify that <span class="name-inline">{{ $payload['full_name'] }}</span>,
        bearing Student Number {{ $payload['student_number'] }}, is officially enrolled at
        {{ $payload['institution'] }}, {{ $payload['campus'] }}, for the period stated below.
    </p>

    <table class="fields">
        <tr><td class="label">Program</td><td class="value">{{ $payload['program'] }}</td></tr>
        <tr><td class="label">College</td><td class="value">{{ $payload['college'] }}</td></tr>
        <tr><td class="label">Year level</td><td class="value">{{ $payload['year_level'] ?? '—' }}</td></tr>
        <tr><td class="label">Semester</td><td class="value">{{ $payload['semester'] ?? '—' }}</td></tr>
        <tr><td class="label">Academic year</td><td class="value">{{ $payload['academic_year'] ?? '—' }}</td></tr>
        <tr><td class="label">Enrolment status</td><td class="value" style="text-transform:capitalize">{{ $payload['status'] ?? 'Enrolled' }}</td></tr>
    </table>

    <p class="indent">
        This certification is issued upon the request of the above-named student
        @if (!empty($payload['purpose']))
            for the purpose of {{ $payload['purpose'] }}.
        @else
            for whatever legal purpose it may serve.
        @endif
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
