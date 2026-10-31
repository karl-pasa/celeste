<div>
    @if ($activeBatch)
        <div class="card-celeste mb-3">
            <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div><i class="bi bi-collection"></i> <strong>{{ $activeBatch->label }}</strong>
                        <span class="text-muted-celeste">({{ $activeBatch->reference }})</span></div>
                    <div class="text-muted-celeste" style="font-size:.8125rem">
                        Showing only the documents of this batch.
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('registrar.batches.download', $activeBatch->id) }}" class="btn btn-sm btn-psu">
                        <i class="bi bi-file-earmark-zip"></i> Download all (ZIP)
                    </a>
                    <button wire:click="$set('batch', '')" class="btn btn-sm btn-psu-outline">Show all documents</button>
                </div>
            </div>
        </div>
    @endif

    <div class="card-celeste">
        <div class="p-3 border-bottom">
            {{-- Row 1: what to look for --}}
            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="search" id="search" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Serial, fingerprint, name, or student number">
                </div>
                <div class="col-6 col-lg-3">
                    <label for="typeFilter" class="form-label">Document</label>
                    <select id="typeFilter" wire:model.live="type" class="form-select">
                        <option value="">All documents</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" wire:model.live="status" class="form-select">
                        <option value="">Any</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                        <option value="superseded">Superseded</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: which class --}}
            <div class="row g-2 align-items-end mt-1">
                <div class="col-lg-4">
                    <label for="collegeFilter" class="form-label">College</label>
                    <select id="collegeFilter" wire:model.live="college" class="form-select">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="programFilter" class="form-label">Program</label>
                    <select id="programFilter" wire:model.live="program" class="form-select" @disabled($college === '')>
                        <option value="">{{ $college === '' ? 'Choose a college first' : 'All programs' }}</option>
                        @foreach ($programs as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="yearLevelFilter" class="form-label">Year level</label>
                    <select id="yearLevelFilter" wire:model.live="yearLevel" class="form-select" @disabled($program === '')>
                        <option value="">All</option>
                        @foreach ($yearLevels as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="sectionFilter" class="form-label">Section</label>
                    <select id="sectionFilter" wire:model.live="section" class="form-select" @disabled($yearLevel === '')>
                        <option value="">All</option>
                        @foreach ($sections as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Row 3: the day it was issued --}}
            <div class="row g-2 align-items-end mt-1">
                <div class="col-6 col-lg-3">
                    <label for="dateFilter" class="form-label">Date issued</label>
                    <input type="date" id="dateFilter" wire:model.live="date" class="form-control">
                </div>
                <div class="col-lg-2 ms-lg-auto">
                    <button wire:click="clearFilters" class="btn btn-psu-outline w-100" title="Clear all filters">
                        <i class="bi bi-x-lg"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-celeste">
                <thead>
                    <tr>
                        <th role="button" wire:click="sortBy('serial_number')">
                            Serial
                            @if ($sort === 'serial_number') <i class="bi bi-caret-{{ $direction === 'asc' ? 'up' : 'down' }}-fill"></i> @endif
                        </th>
                        <th>Document</th>
                        <th>Issued to</th>
                        <th role="button" wire:click="sortBy('issued_on')">
                            Issued
                            @if ($sort === 'issued_on') <i class="bi bi-caret-{{ $direction === 'asc' ? 'up' : 'down' }}-fill"></i> @endif
                        </th>
                        <th>Time</th>
                        <th role="button" wire:click="sortBy('verification_count')">Checks</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr wire:key="cert-{{ $certificate->id }}">
                            <td>
                                <div class="serial">{{ $certificate->serial_number }}</div>
                                @if ($certificate->batch)
                                    <div class="text-muted-celeste" style="font-size:.75rem">{{ $certificate->batch->label }}</div>
                                @endif
                                @unless ($integrity[$certificate->id])
                                    <span class="badge-celeste badge-tampered mt-1">
                                        <i class="bi bi-exclamation-octagon"></i> Fingerprint mismatch
                                    </span>
                                @endunless
                            </td>
                            <td><span class="badge-celeste badge-type">{{ $certificate->type_label }}</span></td>
                            <td>
                                <div>{{ $certificate->studentRecord?->full_name }}</div>
                                <div class="text-muted-celeste" style="font-size:.75rem">{{ $certificate->studentRecord?->student_number }}</div>
                            </td>
                            <td class="text-muted-celeste">{{ $certificate->issued_on?->format('M j, Y') }}</td>
                            <td class="text-muted-celeste text-nowrap">{{ $certificate->created_at?->format('g:i A') }}</td>
                            <td>{{ number_format($certificate->verification_count) }}</td>
                            <td>
                                <span class="badge-celeste {{ match ($certificate->status) {
                                    'issued' => 'badge-issued',
                                    'revoked' => 'badge-revoked',
                                    default => 'badge-superseded',
                                } }}">{{ ucfirst($certificate->status) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('registrar.certificates.show', $certificate) }}" class="btn btn-psu-outline" title="Open">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('certificates.download', $certificate) }}" class="btn btn-psu-outline" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    @if ($certificate->status === 'issued')
                                        <button wire:click="confirm({{ $certificate->id }}, 'reissue')" class="btn btn-psu-outline" title="Reissue">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button wire:click="confirm({{ $certificate->id }}, 'revoke')" class="btn btn-psu-outline" title="Revoke"
                                                style="color:var(--psu-red)">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">
                            <div class="empty">
                                <div class="empty-icon"><i class="bi bi-search"></i></div>
                                <h6>Nothing matches these filters</h6>
                                <p>Clear the filters, or generate a document to get started.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $certificates->links() }}
        </div>
    </div>

    {{-- Revoke / reissue --}}
    @if ($this->actingCertificate)
        <div class="modal d-block" tabindex="-1" style="background:rgba(10,26,60,.55)" wire:key="modal-{{ $actingOn }}">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:0;border-radius:var(--radius-lg)">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title">
                            {{ $action === 'revoke' ? 'Revoke this document' : 'Reissue this document' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cancel" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted-celeste" style="font-size:.875rem">
                            @if ($action === 'revoke')
                                Anyone who verifies <span class="serial">{{ $this->actingCertificate->serial_number }}</span>
                                will be told it is void, and the reason below will be shown to them.
                                The record itself is kept.
                            @else
                                A replacement will be generated for
                                {{ $this->actingCertificate->studentRecord?->full_name }} with a new serial and
                                fingerprint. <span class="serial">{{ $this->actingCertificate->serial_number }}</span>
                                will be marked superseded so old printed copies still resolve to an explanation.
                            @endif
                        </p>

                        <label for="reason" class="form-label">Reason</label>
                        <textarea id="reason" wire:model="reason" rows="3"
                                  class="form-control @error('reason') is-invalid @enderror"
                                  placeholder="{{ $action === 'revoke' ? 'e.g. Issued against an incorrect student record' : 'e.g. Corrected spelling of the middle name' }}"></textarea>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button class="btn btn-psu-outline" wire:click="cancel">Cancel</button>
                        @if ($action === 'revoke')
                            <button class="btn btn-psu" wire:click="revoke" style="background:var(--psu-red)">
                                <i class="bi bi-slash-circle"></i> Revoke document
                            </button>
                        @else
                            <button class="btn btn-psu" wire:click="reissue">
                                <i class="bi bi-arrow-repeat"></i> Reissue document
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>