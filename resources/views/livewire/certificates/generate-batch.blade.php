<div>
    @if ($this->batch)
        <div class="card-celeste mb-3" wire:key="batch-{{ $this->batch->id }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-check-circle-fill" style="color:var(--psu-green)"></i> {{ $this->batch->reference }} finished</span>
                <div class="d-flex gap-2">
                    @if ($this->batch->generated > 0)
                        <a href="{{ route('registrar.batches.download', $this->batch->id) }}" class="btn btn-sm btn-psu">
                            <i class="bi bi-file-earmark-zip"></i> Download all (ZIP)
                        </a>
                    @endif
                    <a href="{{ route('registrar.certificates', ['batch' => $this->batch->id]) }}" class="btn btn-sm btn-psu-outline">
                        View the documents
                    </a>
                </div>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem">{{ $this->batch->generated }}</div>
                        <div class="stat-label">Generated</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem;color:{{ $this->batch->failed ? 'var(--psu-red)' : 'var(--psu-navy-800)' }}">
                            {{ $this->batch->failed }}
                        </div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem">{{ $this->batch->total }}</div>
                        <div class="stat-label">Requested</div>
                    </div>
                </div>

                @if ($this->batch->errors)
                    <hr>
                    <h6 class="mb-2" style="font-size:.875rem">Records that did not generate</h6>
                    <ul class="mb-0 ps-3" style="font-size:.8125rem;color:var(--ink-muted)">
                        @foreach ($this->batch->errors as $error)
                            <li>{{ $error['student'] ?? $error['student_id'] }} — {{ $error['message'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card-celeste">
                <div class="card-header">Batch settings</div>
                <div class="p-3">
                    <label for="label" class="form-label">Batch name <span style="color:var(--psu-red)">*</span></label>
                    <input type="text" id="label" wire:model.live.debounce.300ms="label" required
                           class="form-control mb-1 @error('label') is-invalid @enderror"
                           placeholder="Required. Fills in from the filters, or type your own">
                    @error('label') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                    <div class="mb-3"></div>

                    <label for="batchType" class="form-label">Document to generate</label>
                    <select id="batchType" wire:model.live="documentType" class="form-select mb-3">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="divider-label my-3">Narrow the list</div>

                    <label for="college" class="form-label">College</label>
                    <select id="college" wire:model.live="college" class="form-select mb-2">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <label for="program" class="form-label">Program</label>
                    <select id="program" wire:model.live="program" class="form-select mb-2" @disabled($college === '')>
                        <option value="">{{ $college === '' ? 'Choose a college first' : 'All programs' }}</option>
                        @foreach ($programs as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <label for="yearLevel" class="form-label">Year level</label>
                    <select id="yearLevel" wire:model.live="yearLevel" class="form-select mb-2" @disabled($program === '')>
                        <option value="">{{ $program === '' ? 'Choose a program first' : 'All year levels' }}</option>
                        @foreach ($yearLevels as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <label for="section" class="form-label">Section</label>
                    <select id="section" wire:model.live="section" class="form-select" @disabled($yearLevel === '')>
                        <option value="">{{ $yearLevel === '' ? 'Choose a year level first' : 'All sections' }}</option>
                        @foreach ($sections as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card-celeste">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Student records</span>
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                               placeholder="Search name or number" style="width:200px">
                        <span class="badge-celeste badge-type">{{ count($selected) }} selected</span>
                    </div>
                </div>

                @error('selected')
                    <div class="alert alert-danger py-2 px-3 m-3 mb-0 small">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table table-celeste">
                        <thead>
                            <tr>
                                <th style="width:38px">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectPage" aria-label="Select this page">
                                </th>
                                <th>Student</th>
                                <th>Number</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->students as $record)
                                <tr wire:key="student-{{ $record->id }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input" value="{{ $record->id }}"
                                               wire:model.live="selected" aria-label="Select {{ $record->full_name }}"
                                               @checked(in_array((string) $record->id, $selected, true))>
                                    </td>
                                    <td>{{ $record->full_name }}</td>
                                    <td class="serial">{{ $record->student_number }}</td>
                                    <td class="text-muted-celeste">{{ $record->program }}</td>
                                    <td><span class="badge-celeste badge-type text-capitalize">{{ $record->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="bi bi-funnel"></i></div>
                                        <h6>No records match these filters</h6>
                                        <p>Widen the college, program, year level, or section filter to see more students.</p>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>{{ $this->students->links() }}</div>
                    <button wire:click="generate" class="btn btn-psu" wire:loading.attr="disabled" wire:target="generate"
                            @disabled(count($selected) === 0 || trim($label) === '')>
                        <span wire:loading.remove wire:target="generate">
                            <i class="bi bi-files"></i> Generate {{ count($selected) ?: '' }} document{{ count($selected) === 1 ? '' : 's' }}
                        </span>
                        <span wire:loading wire:target="generate">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generating and hashing…
                        </span>
                    </button>
                </div>
                @if (trim($label) === '')
                    <div class="px-3 pb-3 text-muted-celeste" style="font-size:.75rem">Enter a batch name to enable Generate.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-3">
        <div class="card-celeste">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history"></i> History</span>
                <span class="text-muted-celeste" style="font-size:.8125rem">Previous batches</span>
            </div>

            <div class="table-responsive">
                <table class="table table-celeste">
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Document</th>
                            <th class="text-center">Generated</th>
                            <th class="text-center">Failed</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->history as $batch)
                            <tr wire:key="history-{{ $batch->id }}">
                                <td>
                                    <div>{{ $batch->label }}</div>
                                    <div class="serial text-muted-celeste" style="font-size:.75rem">{{ $batch->reference }}</div>
                                </td>
                                <td class="text-muted-celeste">{{ $types[$batch->document_type] ?? $batch->document_type }}</td>
                                <td class="text-center">{{ $batch->generated }} <span class="text-muted-celeste">/ {{ $batch->total }}</span></td>
                                <td class="text-center" style="{{ $batch->failed ? 'color:var(--psu-red)' : '' }}">{{ $batch->failed }}</td>
                                <td class="text-muted-celeste">{{ $batch->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('registrar.certificates', ['batch' => $batch->id]) }}" class="btn btn-psu-outline" title="View the documents">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($batch->generated > 0)
                                            <a href="{{ route('registrar.batches.download', $batch->id) }}" class="btn btn-psu-outline" title="Download all (ZIP)">
                                                <i class="bi bi-file-earmark-zip"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                                    <h6>No batches yet</h6>
                                    <p>Batches you generate will be listed here, so you can find and download them later.</p>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top">{{ $this->history->links() }}</div>
        </div>

    </div>
</div>