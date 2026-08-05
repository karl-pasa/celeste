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
                <div class="card-header">Your record</div>
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Name</dt><dd>{{ $record->full_name }}</dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $record->student_number }}</dd></div>
                        <div class="detail-row"><dt>College</dt><dd>{{ $record->college }}</dd></div>
                        <div class="detail-row"><dt>Program</dt><dd>{{ $record->program }}</dd></div>
                        <div class="detail-row"><dt>Status</dt><dd class="text-capitalize">{{ $record->status }}</dd></div>
                        @if ($record->date_graduated)
                            <div class="detail-row"><dt>Graduated</dt><dd>{{ $record->date_graduated->format('F j, Y') }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-celeste h-100">
                <div class="card-header">Sharing a document</div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-2" style="font-size:.875rem">
                        Send the PDF as it is. The QR code on it lets an employer or school confirm it is real
                        without contacting the Registrar.
                    </p>
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        A printed copy works the same way — the code survives photocopying.
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
                    <tr><th>Document</th><th>Serial</th><th>Issued</th><th>Status</th><th class="text-end">Actions</th></tr>
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
                            <td class="text-end">
                                @if ($certificate->status === 'issued')
                                    <a href="{{ route('certificates.download', $certificate) }}" class="btn btn-sm btn-psu">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="{{ route('certificates.print', $certificate) }}" target="_blank" class="btn btn-sm btn-psu-outline">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                @else
                                    <span class="text-muted-celeste" style="font-size:.8125rem">Not available</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
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
