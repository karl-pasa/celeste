<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-celeste">
            <div class="card-header">Document details</div>
            <div class="p-3 p-md-4">

                {{-- Step 1: student --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="step-pill">1</span>
                    <label class="form-label mb-0">Find the student record</label>
                </div>

                @if ($this->student)
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3"
                         style="background:var(--psu-navy-050);border:1px solid var(--line)">
                        <div>
                            <div class="fw-semibold">{{ $this->student->full_name }}</div>
                            <div class="text-muted-celeste" style="font-size:.8125rem">
                                <span class="serial">{{ $this->student->student_number }}</span>
                                · {{ $this->student->program }}
                                · <span class="text-capitalize">{{ $this->student->status }}</span>
                            </div>
                        </div>
                        <button wire:click="clearStudent" class="btn btn-sm btn-psu-outline">Change</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="form-control @error('studentId') is-invalid @enderror"
                           placeholder="Search by name or student number" autocomplete="off">
                    @error('studentId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    @if ($this->results->isNotEmpty())
                        <div class="list-group mt-2 mb-3">
                            @foreach ($this->results as $record)
                                <button type="button" wire:click="selectStudent({{ $record->id }})"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <span>
                                        <span class="d-block">{{ $record->full_name }}</span>
                                        <span class="text-muted-celeste" style="font-size:.8125rem">
                                            {{ $record->student_number }} · {{ $record->program }}
                                        </span>
                                    </span>
                                    <span class="badge-celeste badge-type text-capitalize">{{ $record->status }}</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif (strlen($search) >= 2)
                        <p class="text-muted-celeste mt-2 mb-3" style="font-size:.8125rem">
                            No records match “{{ $search }}”. Check the spelling or the student number.
                        </p>
                    @else
                        <p class="text-muted-celeste mt-2 mb-3" style="font-size:.8125rem">
                            Type at least two characters to search.
                        </p>
                    @endif
                @endif

                {{-- Step 2: document type --}}
                <div class="d-flex align-items-center gap-2 mb-2 mt-4">
                    <span class="step-pill">2</span>
                    <label class="form-label mb-0">Choose the document</label>
                </div>

                <div class="row g-2 mb-3">
                    @foreach ($types as $value => $label)
                        <div class="col-sm-6">
                            <label class="d-flex align-items-center gap-2 p-3 rounded-3 h-100"
                                   style="border:1px solid {{ $documentType === $value ? 'var(--psu-navy-500)' : 'var(--line)' }};
                                          background:{{ $documentType === $value ? 'var(--psu-navy-050)' : '#fff' }};cursor:pointer">
                                <input type="radio" class="form-check-input mt-0" wire:model.live="documentType" value="{{ $value }}">
                                <span style="font-size:.875rem">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                @if ($this->eligibility)
                    <div class="alert d-flex gap-2 py-2 px-3 mb-3" style="background:var(--psu-gold-soft);border:1px solid #f0dcae;color:#8a5c0c;font-size:.8125rem">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>{{ $this->eligibility }}</span>
                    </div>
                @endif

                {{-- Step 3: issuance --}}
                <div class="d-flex align-items-center gap-2 mb-2 mt-4">
                    <span class="step-pill">3</span>
                    <label class="form-label mb-0">Issuance</label>
                </div>

                <div class="row g-3">
                    <div class="col-sm-5">
                        <label for="issuedOn" class="form-label">Date of issue</label>
                        <input type="date" id="issuedOn" wire:model="issuedOn"
                               class="form-control @error('issuedOn') is-invalid @enderror">
                        @error('issuedOn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-7">
                        <label for="purpose" class="form-label">Purpose <span class="text-muted-celeste">(optional)</span></label>
                        <input type="text" id="purpose" wire:model="purpose" class="form-control"
                               placeholder="e.g. Employment requirement" maxlength="160">
                    </div>
                </div>

                <button wire:click="generate" class="btn btn-psu w-100 mt-4" wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate">
                        <i class="bi bi-shield-lock"></i> Generate, hash, and stamp the QR
                    </span>
                    <span wire:loading wire:target="generate">
                        <span class="spinner-border spinner-border-sm me-1"></span> Building the document…
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if ($this->issued)
            <div class="card-celeste" wire:key="issued-{{ $this->issued->id }}">
                <div class="card-header d-flex align-items-center gap-2" style="color:#157a34">
                    <i class="bi bi-check-circle-fill"></i> Document issued
                </div>
                <div class="p-3 p-md-4 text-center">
                    <img src="{{ route('certificates.qr', $this->issued) }}" alt="QR code for {{ $this->issued->serial_number }}"
                         class="img-fluid mb-3" style="max-width:170px">

                    <div class="serial mb-1">{{ $this->issued->serial_number }}</div>
                    <div class="text-muted-celeste mb-3" style="font-size:.8125rem">
                        {{ $this->issued->type_label }} · {{ $this->issued->studentRecord?->full_name }}
                    </div>

                    <div class="text-start">
                        <div class="form-label">Fingerprint (SHA-256)</div>
                        <div class="hash-chip d-block mb-3">{{ $this->issued->content_hash }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('certificates.download', $this->issued) }}" class="btn btn-psu flex-fill btn-sm">
                            <i class="bi bi-download"></i> Download
                        </a>
                        <a href="{{ route('certificates.print', $this->issued) }}" target="_blank" class="btn btn-psu-outline flex-fill btn-sm">
                            <i class="bi bi-printer"></i> Print
                        </a>
                    </div>
                    <a href="{{ route('registrar.certificates.show', $this->issued) }}" class="btn btn-psu-outline btn-sm w-100 mt-2">
                        Open the record
                    </a>
                </div>
            </div>
        @else
            <div class="card-celeste">
                <div class="card-header">What happens when you generate</div>
                <div class="p-3 p-md-4">
                    <ol class="ps-3 mb-0" style="font-size:.875rem;line-height:1.9;color:var(--ink-muted)">
                        <li>A snapshot of every printed field is written to the record.</li>
                        <li>That snapshot is hashed with SHA-256 and a server-side key.</li>
                        <li>A QR code pointing at the public verification page is generated.</li>
                        <li>The PDF is rendered with the QR already embedded, then fingerprinted itself.</li>
                        <li>Only then does the document become downloadable or printable.</li>
                    </ol>
                    <p class="text-muted-celeste mt-3 mb-0" style="font-size:.8125rem">
                        Editing a record afterwards breaks its fingerprint, and verification will report the
                        document as altered. Use reissue instead — it supersedes the old copy and keeps the trail.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
