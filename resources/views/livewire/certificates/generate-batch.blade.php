<div>
    @if ($this->batch)
        <div class="card-celeste mb-3" wire:key="batch-{{ $this->batch->id }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-check-circle-fill" style="color:var(--psu-green)"></i> {{ $this->batch->reference }} finished</span>
                <a href="{{ route('registrar.certificates') }}?q={{ $this->batch->reference }}" class="btn btn-sm btn-psu-outline">
                    View the documents
                </a>
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
            <div class="card-celeste mb-3">
                <div class="card-header">Batch settings</div>
                <div class="p-3">
                    <label for="label" class="form-label">Batch name</label>
                    <input type="text" id="label" wire:model="label"
                           class="form-control mb-3 @error('label') is-invalid @enderror"
                           placeholder="e.g. CAS graduates, Class of 2026">
                    @error('label') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

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
                    <select id="program" wire:model.live="program" class="form-select mb-2">
                        <option value="">All programs</option>
                        @foreach ($programs as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>

                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" wire:model.live="status" class="form-select">
                        <option value="">Any status</option>
                        <option disabled>──────────</option>
                        <option value="enrolled">Enrolled</option>
                        <option value="graduated">Graduated</option>
                        <option value="transferred">Transferred</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="card-celeste">
                <div class="card-header">Or upload a list</div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-2" style="font-size:.8125rem">
                        A CSV with student numbers in the first column. A header row is fine.
                    </p>
                    <input type="file" wire:model="csv" class="form-control mb-2" accept=".csv,text/csv">
                    @error('csv') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

                    <button wire:click="importCsv" class="btn btn-psu-outline btn-sm w-100" wire:loading.attr="disabled" wire:target="csv,importCsv">
                        <i class="bi bi-upload"></i> Match and select
                    </button>

                    @if (session('batch-import'))
                        <p class="mb-0 mt-2" style="font-size:.8125rem;color:var(--psu-navy-600)">{{ session('batch-import') }}</p>
                    @endif
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
                                               wire:model.live="selected" aria-label="Select {{ $record->full_name }}">
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
                                        <p>Widen the college, program, or status filter to see more students.</p>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>{{ $this->students->links() }}</div>
                    <button wire:click="generate" class="btn btn-psu" wire:loading.attr="disabled" wire:target="generate"
                            @disabled(count($selected) === 0)>
                        <span wire:loading.remove wire:target="generate">
                            <i class="bi bi-files"></i> Generate {{ count($selected) ?: '' }} document{{ count($selected) === 1 ? '' : 's' }}
                        </span>
                        <span wire:loading wire:target="generate">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generating and hashing…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>