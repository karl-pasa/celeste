@extends('layouts.app')

@section('title', 'My documents')
@section('subtitle', 'Everything the Registrar has issued to you')

@section('content')
@if (! $record)
    <div class="card-celeste">
        <div class="empty">
            <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
            <h6>We could not find your student record</h6>
            <p>Your account is not yet linked to a record. Contact the Office of the University Registrar
               at {{ config('celeste.institution.registrar_email') }} to have it connected.</p>
        </div>
    </div>
@else
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card-celeste h-100">
                <div class="card-header">Your information</div>
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Name</dt><dd>{{ $record->full_name }}</dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $record->student_number }}</dd></div>
                        <div class="detail-row"><dt>Email</dt><dd>{{ $record->email ?: auth()->user()->email }}</dd></div>
                        <div class="detail-row"><dt>College</dt><dd>{{ $record->college }}</dd></div>
                        <div class="detail-row"><dt>Program</dt><dd>{{ $record->program }}</dd></div>
                        @if ($record->major)
                            <div class="detail-row"><dt>Major</dt><dd>{{ $record->major }}</dd></div>
                        @endif
                        @if ($record->year_level)
                            <div class="detail-row"><dt>Year level</dt><dd>{{ $record->year_level }}</dd></div>
                        @endif
                        @if ($record->section)
                            <div class="detail-row"><dt>Section</dt><dd>{{ $record->section }}</dd></div>
                        @endif
                        @if ($record->semester || $record->academic_year)
                            <div class="detail-row"><dt>Term</dt>
                                <dd>{{ trim($record->semester . ($record->academic_year ? ', A.Y. ' . $record->academic_year : '')) }}</dd>
                            </div>
                        @endif
                        <div class="detail-row"><dt>Status</dt><dd class="text-capitalize">{{ $record->status }}</dd></div>
                        @if ($record->date_admitted)
                            <div class="detail-row"><dt>Date admitted</dt><dd>{{ $record->date_admitted->format('F j, Y') }}</dd></div>
                        @endif
                        @if ($record->date_graduated)
                            <div class="detail-row"><dt>Graduated</dt><dd>{{ $record->date_graduated->format('F j, Y') }}</dd></div>
                        @endif
                        @if ($record->latin_honor)
                            <div class="detail-row"><dt>Honors</dt><dd>{{ $record->latin_honor }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-celeste h-100">
                <div class="card-header">Getting a copy</div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-2" style="font-size:.875rem">
                        Copies of your documents are released by the Office of the University Registrar.
                        This page lists what has been issued to you and whether each one is still valid.
                    </p>
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        To request a copy, visit the Registrar's office.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-celeste">
        <div class="card-header">Issued documents</div>
        <div class="table-responsive">
            <table class="table table-celeste">
                <thead>
                    <tr><th>Document</th><th>Serial</th><th>Issued</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td>{{ $certificate->type_label }}</td>
                            <td class="serial">{{ $certificate->serial_number }}</td>
                            <td class="text-muted-celeste">{{ $certificate->issued_on?->format('M j, Y') }}</td>
                            <td>
                                <span class="badge-celeste {{ match ($certificate->status) {
                                    'issued' => 'badge-issued', 'revoked' => 'badge-revoked', default => 'badge-superseded',
                                } }}">{{ ucfirst($certificate->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="empty">
                                <div class="empty-icon"><i class="bi bi-file-earmark"></i></div>
                                <h6>No documents yet</h6>
                                <p>Request a document at the Office of the University Registrar. It will appear here once issued.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection