@extends('pdf._shell')

@section('document')
    <div class="doc-title">OFFICIAL TRANSCRIPT OF RECORDS</div>
    <div class="doc-sub">This transcript is invalid if it does not bear the seal of the University</div>

    <table class="fields">
        <tr>
            <td class="label">Name</td>
            <td class="value">{{ $payload['full_name'] }}</td>
            <td class="label">Student number</td>
            <td class="value">{{ $payload['student_number'] }}</td>
        </tr>
        <tr>
            <td class="label">College</td>
            <td class="value">{{ $payload['college'] }}</td>
            <td class="label">Date admitted</td>
            <td class="value">
                {{ !empty($payload['date_admitted']) ? \Illuminate\Support\Carbon::parse($payload['date_admitted'])->format('F Y') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td class="value">{{ $payload['program'] }}</td>
            <td class="label">Date graduated</td>
            <td class="value">
                {{ !empty($payload['date_graduated']) ? \Illuminate\Support\Carbon::parse($payload['date_graduated'])->format('F j, Y') : 'Not yet graduated' }}
            </td>
        </tr>
    </table>

    @php
        // Group the stored grade rows by term so the transcript reads chronologically.
        $terms = collect($payload['grades'] ?? [])->groupBy(
            fn ($row) => trim(($row['academic_year'] ?? 'Unspecified') . ' · ' . ($row['semester'] ?? ''))
        );
    @endphp

    @if ($terms->isEmpty())
        <p style="text-align:center; color:#8a94ad; padding:24px 0">
            No academic records are on file for this student.
        </p>
    @else
        <table class="grades">
            <thead>
                <tr>
                    <th align="left" width="16%">Course code</th>
                    <th align="left">Descriptive title</th>
                    <th width="10%">Units</th>
                    <th width="10%">Grade</th>
                    <th width="14%">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($terms as $term => $rows)
                    <tr class="term">
                        <td colspan="5">{{ $term }}</td>
                    </tr>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['code'] ?? '' }}</td>
                            <td>{{ $row['title'] ?? '' }}</td>
                            <td class="num">{{ number_format((float) ($row['units'] ?? 0), 1) }}</td>
                            <td class="num">{{ $row['grade'] ?? '' }}</td>
                            <td class="num">{{ $row['remarks'] ?? 'Passed' }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr>
                    <td colspan="2" align="right" style="font-weight:bold">Total units earned</td>
                    <td class="num" style="font-weight:bold">{{ number_format((float) ($payload['total_units'] ?? 0), 1) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        @if (!empty($payload['gwa']))
            <p style="margin-top:10px">
                <strong>General weighted average:</strong> {{ number_format((float) $payload['gwa'], 3) }}
                @if (!empty($payload['latin_honor']))
                    &nbsp;·&nbsp; <strong>Honor:</strong> {{ $payload['latin_honor'] }}
                @endif
            </p>
        @endif
    @endif

    <p style="margin-top:14px; font-size:8pt; color:#5b6784">
        <strong>Grading system.</strong> 1.00 is the highest passing grade and 3.00 the lowest;
        5.00 is failure. INC means incomplete, DRP means officially dropped.
        This transcript covers all work completed at {{ $payload['institution'] }} as of the date of issue.
    </p>

    <table class="sig-block" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%">
                <div class="sig-line">
                    <div class="sig-name">{{ config('celeste.officials.records_officer') }}</div>
                    <div class="sig-role">Records Officer</div>
                </div>
            </td>
            <td width="50%">
                <div class="sig-line">
                    <div class="sig-name">{{ config('celeste.officials.registrar') }}</div>
                    <div class="sig-role">University Registrar</div>
                </div>
            </td>
        </tr>
    </table>
@endsection
