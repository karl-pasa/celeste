@extends('layouts.app')

@section('title', 'Student records')
@section('subtitle', 'The source data every generated document is built from')

@section('content')
<div class="card-celeste">
    <div class="table-responsive">
        <table class="table table-celeste">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Number</th>
                    <th>College</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse (\App\Models\StudentRecord::withCount('certificates')->orderBy('last_name')->paginate(20) as $record)
                    <tr>
                        <td>{{ $record->full_name }}</td>
                        <td class="serial">{{ $record->student_number }}</td>
                        <td class="text-muted-celeste">{{ $record->college }}</td>
                        <td class="text-muted-celeste">{{ $record->program }}</td>
                        <td><span class="badge-celeste badge-type text-capitalize">{{ $record->status }}</span></td>
                        <td>{{ $record->certificates_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('registrar.certificates') }}?q={{ $record->student_number }}"
                               class="btn btn-sm btn-psu-outline">View documents</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-people"></i></div>
                            <h6>No student records loaded</h6>
                            <p>Run the seeder or import records before generating documents.</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
