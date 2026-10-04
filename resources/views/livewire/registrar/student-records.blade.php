{{--
    Student Records.

    One component, two states: the list, or the form. A full panel rather
    than a modal -- the record carries forty odd fields, and a dialog that
    scrolls inside a page that also scrolls is harder to fill in than a page
    that simply replaces the list.

    Styling follows the classes already used elsewhere in the registrar
    pages: card-celeste, table-celeste, badge-celeste, btn-psu, empty.
--}}
<div>

    {{-- ─────────────── messages ─────────────── --}}
    {{-- Rendered by Blade from component properties rather than held in
         browser state: nothing here depends on Alpine being present, so the
         banner cannot end up visible and empty if Alpine fails to start. --}}
    @if ($flash)
        <div class="alert alert-success py-2 px-3 small mb-3 d-flex align-items-start gap-2">
            <i class="bi bi-check-circle mt-1"></i>
            <div class="flex-grow-1">{{ $flash }}</div>
            <button type="button" class="btn-close" wire:click="clearMessages"
                    aria-label="Dismiss"></button>
        </div>
    @endif

    @if ($blocked)
        <div class="alert alert-warning py-2 px-3 small mb-3 d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle mt-1"></i>
            <div class="flex-grow-1">{{ $blocked }}</div>
            <button type="button" class="btn-close" wire:click="clearMessages"
                    aria-label="Dismiss"></button>
        </div>
    @endif


    @if (! $showForm)

        {{-- ═══════════════ LIST ═══════════════ --}}

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="flex-grow-1" style="min-width:230px">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control"
                           placeholder="Student number or last name"
                           wire:model.live.debounce.300ms="search">
                </div>
            </div>

            <select class="form-select" style="max-width:185px" wire:model.live="statusFilter">
                <option value="">All statuses</option>
                @foreach ($this->statuses() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button type="button" class="btn btn-psu-outline" wire:click="openImport">
                <i class="bi bi-upload"></i> Import CSV
            </button>

            <button type="button" class="btn btn-psu" wire:click="create">
                <i class="bi bi-plus-lg"></i> Add student
            </button>
        </div>

        @if ($showImport)
            {{-- Two passes: this panel reports what the file will do, and
                 nothing is written until it is confirmed. Importing several
                 hundred records is not something to discover was wrong
                 afterwards. --}}
            <div class="card-celeste p-3 p-md-4 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Import students from CSV</h6>
                    <button type="button" class="btn btn-sm btn-light" wire:click="closeImport">
                        <i class="bi bi-x-lg"></i> Close
                    </button>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label">CSV file</label>
                        <input type="file" class="form-control @error('csv') is-invalid @enderror"
                               wire:model="csv" accept=".csv,text/csv">
                        @error('csv') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">
                            The first row must be the column headings. Columns the
                            system does not recognise are ignored.
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">If a student number already exists</label>
                        <select class="form-select" wire:model.live="onDuplicate">
                            <option value="skip">Skip that row</option>
                            <option value="update">Update the existing record</option>
                        </select>
                    </div>
                </div>

                <div wire:loading wire:target="csv" class="small text-muted-celeste mt-3">
                    Reading the file…
                </div>

                @if ($preview)
                    @if (! empty($preview['fatal']))
                        <div class="alert alert-danger py-2 px-3 small mt-3 mb-0">
                            {{ $preview['fatal'] }}
                        </div>
                    @else
                        <hr class="my-3">

                        <div class="d-flex flex-wrap gap-4">
                            <div>
                                <div class="small text-muted-celeste">Ready to import</div>
                                <div class="fs-4 fw-bold">{{ $preview['ready'] }}</div>
                            </div>
                            <div>
                                <div class="small text-muted-celeste">New</div>
                                <div class="fs-4 fw-bold">{{ $preview['new'] }}</div>
                            </div>
                            <div>
                                <div class="small text-muted-celeste">Updates</div>
                                <div class="fs-4 fw-bold">{{ $preview['updates'] }}</div>
                            </div>
                            <div>
                                <div class="small text-muted-celeste">Rejected</div>
                                <div class="fs-4 fw-bold">{{ $preview['rejected'] }}</div>
                            </div>
                        </div>

                        @if (! empty($preview['ignored']))
                            <p class="small text-muted-celeste mt-3 mb-0">
                                Columns ignored:
                                {{ implode(', ', $preview['ignored']) }}
                            </p>
                        @endif

                        @if (! empty($preview['errors']))
                            <h6 class="fw-bold mt-4">Rows that will not be imported</h6>
                            <div class="table-responsive" style="max-height:260px; overflow:auto">
                                <table class="table table-celeste mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:70px">Line</th>
                                            <th style="width:140px">Student no.</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($preview['errors'] as $error)
                                            <tr>
                                                <td>{{ $error['line'] }}</td>
                                                <td class="serial">{{ $error['number'] ?: '—' }}</td>
                                                <td class="small">{{ $error['why'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($preview['truncated'] > 0)
                                <p class="small text-muted-celeste mt-2 mb-0">
                                    …and {{ $preview['truncated'] }} more.
                                </p>
                            @endif
                        @endif

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-psu"
                                    wire:click="import"
                                    wire:loading.attr="disabled"
                                    @disabled($preview['ready'] === 0)>
                                <span wire:loading.remove wire:target="import">
                                    <i class="bi bi-check-lg"></i>
                                    Import {{ $preview['ready'] }}
                                    {{ Str::plural('record', $preview['ready']) }}
                                </span>
                                <span wire:loading wire:target="import">Importing…</span>
                            </button>
                            <button type="button" class="btn btn-light" wire:click="closeImport">
                                Cancel
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        <div class="card-celeste">
            <div class="table-responsive">
                <table class="table table-celeste">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Number</th>
                            <th>College</th>
                            <th>Program</th>
                            <th>Year / Section</th>
                            <th>Status</th>
                            <th>Documents</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->records as $record)
                            <tr wire:key="student-{{ $record->id }}">
                                <td>{{ $record->full_name }}</td>
                                <td class="serial">{{ $record->student_number }}</td>
                                <td class="text-muted-celeste">{{ $record->college }}</td>
                                <td class="text-muted-celeste">{{ $record->program }}</td>
                                <td class="text-muted-celeste">
                                    {{ $record->year_level ?: '—' }}@if ($record->section) / {{ $record->section }}@endif
                                </td>
                                <td>
                                    <span class="badge-celeste badge-type text-capitalize">{{ $record->status }}</span>
                                </td>
                                <td>{{ $record->certificates_count }}</td>
                                <td class="text-end text-nowrap">
                                    @if ($pendingDeleteId === $record->id)
                                        {{-- The confirmation replaces the buttons in place,
                                             so the row being deleted is the row being read. --}}
                                        <span class="small me-2">Delete this record?</span>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="delete">Yes, delete</button>
                                        <button type="button" class="btn btn-sm btn-light"
                                                wire:click="cancelDelete">Cancel</button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-psu-outline"
                                                wire:click="edit({{ $record->id }})">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-people"></i></div>
                                    @if (strlen(trim($search)) >= 2 || $statusFilter !== '')
                                        <h6>No record matches your search</h6>
                                        <p>Try a different student number or last name.</p>
                                    @else
                                        <h6>No student records yet</h6>
                                        <p>Use Add student to create the first one.</p>
                                    @endif
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $this->records->links('pagination::bootstrap-5') }}
        </div>

    @else

        {{-- ═══════════════ FORM ═══════════════ --}}

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">{{ $editingId ? 'Edit student record' : 'Add student record' }}</h5>
            <button type="button" class="btn btn-sm btn-light" wire:click="cancel">
                <i class="bi bi-x-lg"></i> Cancel
            </button>
        </div>

        @if ($editingId)
            {{-- Without this the registrar corrects a name, reopens an old
                 transcript, sees the old name, and reports a bug. --}}
            <div class="alert alert-info py-2 px-3 small d-flex gap-2">
                <i class="bi bi-info-circle mt-1"></i>
                <div>
                    Changes here apply to documents issued <strong>from now on</strong>.
                    Documents already issued keep the information they were issued
                    with, because that is what their fingerprint covers. To correct
                    a released document, revoke it and issue a new one.
                </div>
            </div>
        @endif

        <form wire:submit="save">
            <div class="card-celeste p-3 p-md-4">

                {{-- ─────── Student information ─────── --}}
                <h6 class="fw-bold">Student Information</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Student number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('form.student_number') is-invalid @enderror"
                               wire:model="form.student_number">
                        @error('form.student_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('form.last_name') is-invalid @enderror"
                               wire:model="form.last_name">
                        @error('form.last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('form.first_name') is-invalid @enderror"
                               wire:model="form.first_name">
                        @error('form.first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle name</label>
                        <input type="text" class="form-control" wire:model="form.middle_name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Suffix</label>
                        <input type="text" class="form-control" wire:model="form.suffix" placeholder="Jr., III">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sex</label>
                        <select class="form-select" wire:model="form.gender">
                            <option value="">—</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Civil status</label>
                        <select class="form-select @error('form.civil_status') is-invalid @enderror"
                                wire:model="form.civil_status">
                            <option value="">—</option>
                            @foreach ($this->civilStatuses() as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                        @error('form.civil_status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date of birth</label>
                        <input type="date" class="form-control @error('form.birth_date') is-invalid @enderror"
                               wire:model="form.birth_date">
                        @error('form.birth_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Place of birth</label>
                        <input type="text" class="form-control" wire:model="form.birthplace">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Citizenship</label>
                        <input type="text" class="form-control" wire:model="form.nationality" placeholder="Filipino">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Religion</label>
                        <input type="text" class="form-control" wire:model="form.religion">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('form.email') is-invalid @enderror"
                               wire:model="form.email">
                        @error('form.email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact number</label>
                        <input type="text" class="form-control @error('form.contact_number') is-invalid @enderror"
                               wire:model="form.contact_number" placeholder="e.g. 0930 241 8587">
                        @error('form.contact_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- ─────── Permanent address ─────── --}}
                {{-- Held in parts rather than one line. The single-line
                     address the Transfer Credential prints is composed from
                     these on save, so the two cannot disagree. --}}
                <h6 class="fw-bold mt-4">Permanent Address</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        {{-- .live so the server can rebuild the city list below --}}
                        <select class="form-select @error('form.perm_province') is-invalid @enderror"
                                wire:model.live="form.perm_province">
                            <option value="">Select province…</option>
                            @foreach ($this->addressOptions('perm', 'province') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.perm_province') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City / Municipality</label>
                        <select class="form-select @error('form.perm_city') is-invalid @enderror"
                                wire:model.live="form.perm_city"
                                @disabled(empty($form['perm_province']))>
                            <option value="">
                                {{ empty($form['perm_province'])
                                    ? 'Choose a province first'
                                    : 'Select city or municipality…' }}
                            </option>
                            @foreach ($this->addressOptions('perm', 'city') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.perm_city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <select class="form-select @error('form.perm_barangay') is-invalid @enderror"
                                wire:model="form.perm_barangay"
                                @disabled(empty($form['perm_city']))>
                            <option value="">
                                {{ empty($form['perm_city'])
                                    ? 'Choose a city first'
                                    : 'Select barangay…' }}
                            </option>
                            @foreach ($this->addressOptions('perm', 'barangay') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.perm_barangay') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ZIP / Postal code</label>
                        <input type="text" class="form-control" wire:model="form.perm_zip">
                    </div>
                </div>

                {{-- ─────── Temporary address ─────── --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mt-4 mb-2">
                    <h6 class="fw-bold mb-0">Temporary Address</h6>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox"
                               id="sameAddress" wire:model.live="sameAddress">
                        <label class="form-check-label small" for="sameAddress">
                            Same as permanent address
                        </label>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        {{-- .live so the server can rebuild the city list below --}}
                        <select class="form-select @error('form.temp_province') is-invalid @enderror"
                                wire:model.live="form.temp_province"
                                @disabled($sameAddress)>
                            <option value="">Select province…</option>
                            @foreach ($this->addressOptions('temp', 'province') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.temp_province') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City / Municipality</label>
                        <select class="form-select @error('form.temp_city') is-invalid @enderror"
                                wire:model.live="form.temp_city"
                                @disabled($sameAddress || empty($form['temp_province']))>
                            <option value="">
                                {{ empty($form['temp_province'])
                                    ? 'Choose a province first'
                                    : 'Select city or municipality…' }}
                            </option>
                            @foreach ($this->addressOptions('temp', 'city') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.temp_city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <select class="form-select @error('form.temp_barangay') is-invalid @enderror"
                                wire:model="form.temp_barangay"
                                @disabled($sameAddress || empty($form['temp_city']))>
                            <option value="">
                                {{ empty($form['temp_city'])
                                    ? 'Choose a city first'
                                    : 'Select barangay…' }}
                            </option>
                            @foreach ($this->addressOptions('temp', 'barangay') as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('form.temp_barangay') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ZIP / Postal code</label>
                        <input type="text" class="form-control" wire:model="form.temp_zip"
                               @disabled($sameAddress)>
                    </div>
                </div>

                {{-- ─────── Academic ─────── --}}
                <h6 class="fw-bold mt-4">Academic Information</h6>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">College <span class="text-danger">*</span></label>
                        {{-- .live, not the default deferred binding: the programme
                             list below is rebuilt from whatever is chosen here, so
                             the server has to learn about the change immediately. --}}
                        <select class="form-select @error('form.college') is-invalid @enderror"
                                wire:model.live="form.college">
                            <option value="">Select college…</option>
                            @foreach ($this->collegeOptions() as $college)
                                <option value="{{ $college }}">{{ $college }}</option>
                            @endforeach
                        </select>
                        @error('form.college') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Program <span class="text-danger">*</span></label>
                        <select class="form-select @error('form.program') is-invalid @enderror"
                                wire:model="form.program"
                                @disabled(empty($form['college']))>
                            <option value="">
                                {{ empty($form['college'])
                                    ? 'Choose a college first'
                                    : 'Select program…' }}
                            </option>
                            @foreach ($this->programOptions() as $program)
                                <option value="{{ $program }}">{{ $program }}</option>
                            @endforeach
                        </select>
                        @error('form.program') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Major</label>
                        <input type="text" class="form-control" wire:model="form.major">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Year level</label>
                        <select class="form-select @error('form.year_level') is-invalid @enderror"
                                wire:model="form.year_level">
                            <option value="">—</option>
                            @foreach ($this->yearLevels() as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                        @error('form.year_level') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Section</label>
                        <select class="form-select @error('form.section') is-invalid @enderror"
                                wire:model="form.section">
                            <option value="">—</option>
                            @foreach ($this->sections() as $section)
                                <option value="{{ $section }}">{{ $section }}</option>
                            @endforeach
                        </select>
                        @error('form.section') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Semester</label>
                        <select class="form-select" wire:model="form.semester">
                            <option value="">—</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Academic year</label>
                        <input type="text" class="form-control" wire:model="form.academic_year" placeholder="2025-2026">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('form.status') is-invalid @enderror"
                                wire:model="form.status">
                            @foreach ($this->statuses() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('form.status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- ─────── Admission ─────── --}}
                <h6 class="fw-bold mt-4">Admission Data</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Admission type</label>
                        <select class="form-select" wire:model.live="form.admission_type">
                            <option value="">—</option>
                            <option value="New">New</option>
                            <option value="Transferee">Transferee</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date admitted</label>
                        <input type="date" class="form-control" wire:model="form.date_admitted">
                    </div>
                </div>

                {{-- Only the block matching the admission type is shown. A
                     transferee has no "school last attended" in the sense this
                     form means, and leaving both open invites one student's
                     history being typed into both halves. --}}
                @if (($form['admission_type'] ?? '') === 'New')
                <div class="row g-3 mt-1">
                    <div class="col-12"><small class="text-muted-celeste">For new students</small></div>
                    <div class="col-md-5">
                        <label class="form-label">School last attended</label>
                        <input type="text" class="form-control" wire:model="form.adm_new_school">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" wire:model="form.adm_new_address">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year graduated</label>
                        <input type="text" class="form-control" wire:model="form.adm_new_year_graduated">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Course / strand</label>
                        <input type="text" class="form-control" wire:model="form.adm_new_course">
                    </div>
                </div>
                @endif

                @if (($form['admission_type'] ?? '') === 'Transferee')
                <div class="row g-3 mt-1">
                    <div class="col-12"><small class="text-muted-celeste">For transferees</small></div>
                    <div class="col-md-5">
                        <label class="form-label">School transferred from</label>
                        <input type="text" class="form-control" wire:model="form.adm_tr_school">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" wire:model="form.adm_tr_address">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year graduated</label>
                        <input type="text" class="form-control" wire:model="form.adm_tr_year_graduated">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Course</label>
                        <input type="text" class="form-control" wire:model="form.adm_tr_course">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Credential presented</label>
                        <input type="text" class="form-control" wire:model="form.adm_tr_credential">
                    </div>
                </div>
                @endif

                @if (empty($form['admission_type']))
                    <p class="small text-muted-celeste mt-2 mb-0">
                        Choose an admission type above to fill in the school
                        the student came from.
                    </p>
                @endif

                {{-- ─────── Graduation ─────── --}}
                <h6 class="fw-bold mt-4">Graduation Data</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date graduated</label>
                        <input type="date" class="form-control @error('form.date_graduated') is-invalid @enderror"
                               wire:model="form.date_graduated">
                        @error('form.date_graduated') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date conferred</label>
                        <input type="date" class="form-control" wire:model="form.date_conferred">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Board resolution no.</label>
                        <input type="text" class="form-control" wire:model="form.board_resolution_no">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Resolution date</label>
                        <input type="date" class="form-control" wire:model="form.board_resolution_date">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Latin honor</label>
                        <input type="text" class="form-control" wire:model="form.latin_honor">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">General weighted average</label>
                        <input type="text" class="form-control @error('form.general_weighted_average') is-invalid @enderror"
                               wire:model="form.general_weighted_average">
                        @error('form.general_weighted_average') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Awards</label>
                        <input type="text" class="form-control" wire:model="form.awards">
                    </div>
                </div>

                {{-- ─────── Other ─────── --}}
                <h6 class="fw-bold mt-4">Other Information</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">NSTP serial no.</label>
                        <input type="text" class="form-control" wire:model="form.nstp_serial_no">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Program accreditation</label>
                        <input type="text" class="form-control" wire:model="form.program_accreditation">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Granted transfer credentials</label>
                        <textarea class="form-control" rows="2" wire:model="form.granted_transfer_credentials"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" rows="2" wire:model="form.remarks"></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-psu" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-lg"></i>
                            {{ $editingId ? 'Save changes' : 'Add student' }}
                        </span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                    <button type="button" class="btn btn-light" wire:click="cancel">Cancel</button>
                </div>

                <p class="small text-muted-celeste mt-3 mb-0">
                    Subject grades are not edited here. They are maintained
                    separately, because a transcript's rows change far more
                    often than the rest of a student's record.
                </p>
            </div>
        </form>

    @endif
</div>