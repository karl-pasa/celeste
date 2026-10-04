<div>
    <div x-data="{ ok: '', warn: '' }"
         x-on:record-saved.window="ok = $event.detail.message; warn = ''; setTimeout(() => ok = '', 4000)"
         x-on:record-blocked.window="warn = $event.detail.message; ok = ''">

        <div class="alert alert-success py-2 px-3 small" x-show="ok" x-cloak x-text="ok"></div>

        <div class="alert alert-warning py-2 px-3 small d-flex align-items-start gap-2"
             x-show="warn" x-cloak>
            <i class="bi bi-exclamation-triangle mt-1"></i>
            <div class="flex-grow-1" x-text="warn"></div>
            <button type="button" class="btn-close" x-on:click="warn = ''"></button>
        </div>
    </div>


    @if (! $showForm)

        {{-- ═══════════════ LIST ═══════════════ --}}

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="flex-grow-1" style="min-width:220px">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control"
                           placeholder="Student number or last name"
                           wire:model.live.debounce.300ms="search">
                </div>
            </div>

            <select class="form-select" style="max-width:180px"
                    wire:model.live="statusFilter">
                <option value="">All statuses</option>
                @foreach ($this->statuses() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button type="button" class="btn btn-psu" wire:click="create">
                <i class="bi bi-plus-lg"></i> Add student
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Year &amp; Section</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->records as $r)
                        <tr wire:key="student-{{ $r->id }}">
                            <td class="font-monospace small">{{ $r->student_number }}</td>
                            <td>
                                {{ $r->last_name }}, {{ $r->first_name }}
                                @if ($r->middle_name)
                                    {{ mb_substr($r->middle_name, 0, 1) }}.
                                @endif
                                {{ $r->suffix }}
                            </td>
                            <td class="small">{{ $r->program }}</td>
                            <td class="small">
                                {{ $r->year_level ?: '—' }}
                                @if ($r->section)
                                    <span class="text-muted-celeste">/ {{ $r->section }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill
                                    @class([
                                        'text-bg-success'   => $r->status === 'enrolled',
                                        'text-bg-primary'   => $r->status === 'graduated',
                                        'text-bg-secondary' => $r->status === 'transferred',
                                    ])">
                                    {{ $this->statuses()[$r->status] ?? $r->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if ($pendingDeleteId === $r->id)
                                    {{-- Confirmation replaces the buttons in place, so
                                         the row being deleted is the row being read. --}}
                                    <span class="small me-2">Delete this record?</span>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="delete">Yes, delete</button>
                                    <button type="button" class="btn btn-sm btn-light"
                                            wire:click="cancelDelete">Cancel</button>
                                @else
                                    <button type="button" class="btn btn-sm btn-psu-outline"
                                            wire:click="edit({{ $r->id }})">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="confirmDelete({{ $r->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted-celeste py-4">
                                @if (strlen(trim($search)) >= 2 || $statusFilter !== '')
                                    No record matches what you searched for.
                                @else
                                    No student records yet. Use Add student to create one.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $this->records->links() }}

    @else

        {{-- ═══════════════ FORM ═══════════════ --}}

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">
                {{ $editingId ? 'Edit student record' : 'Add student record' }}
            </h5>
            <button type="button" class="btn btn-light btn-sm" wire:click="cancel">
                <i class="bi bi-x-lg"></i> Cancel
            </button>
        </div>

        @if ($editingId)
            {{-- Without this the registrar corrects a name, reopens an old
                 transcript, sees the old name and reports a bug. --}}
            <div class="alert alert-info py-2 px-3 small d-flex gap-2">
                <i class="bi bi-info-circle mt-1"></i>
                <div>
                    Changes here apply to documents issued <strong>from now on</strong>.
                    Documents already issued keep the information they were
                    issued with, because that is what their fingerprint covers.
                    To correct a released document, revoke it and issue a new one.
                </div>
            </div>
        @endif

        <form wire:submit="save">

            {{-- ─────── Identity ─────── --}}
            <h6 class="fw-bold mt-3">Student Information</h6>
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
                    <input type="text" class="form-control" wire:model="form.suffix"
                           placeholder="Jr., III">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select class="form-select" wire:model="form.gender">
                        <option value="">—</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nationality</label>
                    <input type="text" class="form-control" wire:model="form.nationality"
                           placeholder="Filipino">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date of birth</label>
                    <input type="date" class="form-control @error('form.birth_date') is-invalid @enderror"
                           wire:model="form.birth_date">
                    @error('form.birth_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Place of birth</label>
                    <input type="text" class="form-control" wire:model="form.birthplace">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control @error('form.email') is-invalid @enderror"
                           wire:model="form.email">
                    @error('form.email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" wire:model="form.address">
                </div>
            </div>

            {{-- ─────── Academic ─────── --}}
            <h6 class="fw-bold mt-4">Academic Information</h6>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">College <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('form.college') is-invalid @enderror"
                           wire:model="form.college">
                    @error('form.college') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-7">
                    <label class="form-label">Program <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('form.program') is-invalid @enderror"
                           wire:model="form.program">
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
                    <input type="text" class="form-control" wire:model="form.academic_year"
                           placeholder="2025-2026">
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
                    <select class="form-select" wire:model="form.admission_type">
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
                    <textarea class="form-control" rows="2"
                              wire:model="form.granted_transfer_credentials"></textarea>
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

            <p class="small text-muted-celeste mt-3">
                Subject grades are not edited here. They are maintained
                separately, because a transcript's rows change far more often
                than the rest of a student's record.
            </p>
        </form>

    @endif
</div>
