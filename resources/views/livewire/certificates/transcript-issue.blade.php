<div>
    <style>
        .tabs { display:flex; gap:.25rem; background:var(--psu-navy-050); padding:.25rem;
            border-radius:99px; border:1px solid var(--line); width:fit-content; margin-bottom:1rem; }
        .tabs button { border:0; background:transparent; padding:.4rem 1.1rem; border-radius:99px;
            font-size:.8125rem; color:var(--ink-muted); cursor:pointer; }
        .tabs button.on { background:var(--psu-navy-700); color:#fff; font-weight:600; }
        .sec { border:1px solid var(--line); border-radius:var(--radius); margin-bottom:1rem; background:#fff; }
        .sec > .head { background:var(--psu-navy-050); padding:.55rem 1rem; font-size:.7rem;
            letter-spacing:.09em; text-transform:uppercase; font-weight:600; color:var(--psu-navy-700);
            border-bottom:1px solid var(--line); border-radius:var(--radius) var(--radius) 0 0;
            display:flex; justify-content:space-between; align-items:center; gap:1rem; }
        .sec > .body { padding:1rem; }
        .sec .form-label { font-size:.72rem; margin-bottom:.15rem; }
        .sec .form-control, .sec .form-select { font-size:.8125rem; padding:.35rem .6rem; }
        .rowtab th { font-size:.65rem; letter-spacing:.05em; text-transform:uppercase;
            color:var(--ink-muted); font-weight:600; padding:.35rem .25rem;
            border-bottom:1px solid var(--line); }
        .rowtab td { padding:.15rem .12rem; }
        .rowtab input { font-size:.76rem; padding:.25rem .4rem; }
        .pill { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem;
            border-radius:99px; background:var(--psu-navy-050); border:1px solid var(--line);
            font-size:.76rem; color:var(--ink-muted); }
        .pill.good { background:#e6f7ec; border-color:#a8e0bd; color:#157a34; }
        .pill.warn { background:var(--psu-gold-soft); border-color:#f0dcae; color:#8a5c0c; }
        .pill.bad  { background:#fcebee; border-color:#f3c4cc; color:#a52338; }
        .mini { padding:.1rem .4rem; font-size:.7rem; line-height:1.5; }
    </style>

    @if (session('tor-status'))
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small mb-3">
            <i class="bi bi-check-circle"></i> {{ session('tor-status') }}
        </div>
    @endif

    <div class="tabs">
        <button class="{{ $mode === 'single' ? 'on' : '' }}" wire:click="setMode('single')">
            <i class="bi bi-person"></i> One student
        </button>
        <button class="{{ $mode === 'import' ? 'on' : '' }}" wire:click="setMode('import')">
            <i class="bi bi-file-earmark-arrow-up"></i> Import a batch
        </button>
    </div>

    {{-- ═══════════════ IMPORT ═══════════════ --}}
    @if ($mode === 'import')

        @if ($importResult)
            <div class="sec" style="border-left:3px solid var(--psu-green)">
                <div class="body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-check-circle-fill" style="color:var(--psu-green);font-size:1.3rem"></i>
                        <span class="fw-semibold">Import complete</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="pill good">{{ $importResult['created'] }} students added</span>
                        @if ($importResult['updated'])
                            <span class="pill">{{ $importResult['updated'] }} updated</span>
                        @endif
                    </div>
                    <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
                        Their transcript details are saved. To issue a transcript, switch to
                        <strong>One student</strong> and search their name or number — every field
                        will fill in.
                    </p>
                    <div class="d-flex gap-2">
                        <button wire:click="setMode('single')" class="btn btn-psu btn-sm">Issue a transcript</button>
                        <button wire:click="startOverImport" class="btn btn-psu-outline btn-sm">Import another file</button>
                    </div>
                </div>
            </div>
        @else
            <div class="sec">
                <div class="head"><span>1 · Get the template</span></div>
                <div class="body">
                    <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
                        The template carries every column the Transcript of Records prints — personal
                        information, admission data for new and transferee students, graduation data,
                        NSTP serial, accreditation, and remarks. Fill it in and save as CSV.
                    </p>
                    <button wire:click="downloadTemplate" class="btn btn-psu-outline btn-sm">
                        <i class="bi bi-download"></i> Download the transcript CSV template
                    </button>
                    <p class="text-muted-celeste mt-2 mb-0" style="font-size:.72rem">
                        Required columns: <code>student_number</code>, <code>first_name</code>,
                        <code>last_name</code>, <code>program</code>. Dates use YYYY-MM-DD.
                        <code>admission_type</code> is either <code>new</code> or <code>transferee</code>.
                    </p>
                </div>
            </div>

            <div class="sec">
                <div class="head"><span>2 · Upload and check</span></div>
                <div class="body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <input type="file" wire:model="csv" accept=".csv,text/csv" class="form-control">
                            @error('csv') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <button wire:click="checkImport" class="btn btn-psu"
                                    wire:loading.attr="disabled" wire:target="checkImport,csv">
                                <span wire:loading.remove wire:target="checkImport">
                                    <i class="bi bi-search"></i> Check the file
                                </span>
                                <span wire:loading wire:target="checkImport">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Checking…
                                </span>
                            </button>
                        </div>
                    </div>
                    <p class="text-muted-celeste mt-2 mb-0" style="font-size:.72rem">
                        Nothing is saved until you confirm.
                    </p>
                </div>
            </div>

            @if ($importSummary || $importErrors)
                <div class="sec">
                    <div class="head"><span>3 · Review</span></div>
                    <div class="body">
                        @if ($importSummary)
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="pill">{{ $importSummary['total'] }} rows read</span>
                                <span class="pill good">{{ $importSummary['new'] }} new</span>
                                @if ($importSummary['existing'])
                                    <span class="pill warn">{{ $importSummary['existing'] }} already in the database</span>
                                @endif
                                @if ($importSummary['errors'])
                                    <span class="pill bad">{{ $importSummary['errors'] }} problems</span>
                                @endif
                            </div>
                        @endif

                        @if ($importErrors)
                            <div class="p-3 rounded-3 mb-3" style="background:#fcebee;border:1px solid #f3c4cc">
                                <div class="fw-semibold mb-2" style="font-size:.8125rem;color:#a52338">
                                    Fix these in the spreadsheet, then choose the file again
                                </div>
                                <ul class="mb-0 ps-3" style="font-size:.78rem;color:#a52338">
                                    @foreach (array_slice($importErrors, 0, 15) as $e)
                                        <li><strong>Line {{ $e['line'] }}</strong> — {{ $e['message'] }}</li>
                                    @endforeach
                                    @if (count($importErrors) > 15)
                                        <li>… and {{ count($importErrors) - 15 }} more.</li>
                                    @endif
                                </ul>
                            </div>
                        @else
                            <div class="table-responsive mb-3">
                                <table class="table-celeste" style="font-size:.74rem">
                                    <thead>
                                        <tr><th>Number</th><th>Name</th><th>Course</th>
                                            <th>Admission</th><th>Conferred</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach (array_slice($importRows, 0, 8) as $r)
                                            <tr>
                                                <td class="serial">{{ $r['student_number'] }}</td>
                                                <td>{{ trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) }}</td>
                                                <td>{{ $r['program'] ?? '' }}</td>
                                                <td class="text-capitalize">{{ $r['admission_type'] ?? '' }}</td>
                                                <td>{{ $r['date_conferred'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if (count($importRows) > 8)
                                    <p class="text-muted-celeste mb-0" style="font-size:.74rem">
                                        Showing 8 of {{ count($importRows) }} rows.
                                    </p>
                                @endif
                            </div>

                            <button wire:click="runImport" class="btn btn-psu"
                                    wire:loading.attr="disabled" wire:target="runImport">
                                <span wire:loading.remove wire:target="runImport">
                                    <i class="bi bi-database-add"></i> Save {{ $importSummary['total'] ?? 0 }} students
                                </span>
                                <span wire:loading wire:target="runImport">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Saving…
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endif

    {{-- ═══════════════ SINGLE ═══════════════ --}}
    @else
    <div class="row g-3">
        <div class="col-xl-8">

            <div class="sec">
                <div class="head"><span>Student</span></div>
                <div class="body">
                    @if ($this->student)
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3"
                             style="background:var(--psu-navy-050)">
                            <div>
                                <div class="fw-semibold">{{ $this->student->full_name }}</div>
                                <div class="text-muted-celeste" style="font-size:.74rem">
                                    <span class="serial">{{ $this->student->student_number }}</span>
                                    · {{ $this->student->program }} · details filled from the record
                                </div>
                            </div>
                            <button wire:click="clearStudent" class="btn btn-sm btn-psu-outline">Change</button>
                        </div>
                    @else
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                               placeholder="Search an existing student by name or number — or just type the details below">
                        @if ($this->results->isNotEmpty())
                            <div class="list-group mt-2">
                                @foreach ($this->results as $r)
                                    <button type="button" wire:click="selectStudent({{ $r->id }})"
                                            class="list-group-item list-group-item-action py-2">
                                        <span class="d-block" style="font-size:.85rem">{{ $r->full_name }}</span>
                                        <span class="text-muted-celeste" style="font-size:.74rem">
                                            {{ $r->student_number }} · {{ $r->program }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- identity --}}
            <div class="sec">
                <div class="head"><span>Identity</span></div>
                <div class="body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label">Student number</label>
                            <input type="text" wire:model="student_number"
                                   class="form-control @error('student_number') is-invalid @enderror">
                            @error('student_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">First name</label>
                            <input type="text" wire:model="first_name"
                                   class="form-control @error('first_name') is-invalid @enderror">
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Middle name</label>
                            <input type="text" wire:model="middle_name" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Last name</label>
                            <input type="text" wire:model="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror">
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Suffix</label>
                            <input type="text" wire:model="suffix" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-7">
                            <label class="form-label">Course</label>
                            <input type="text" wire:model="program"
                                   class="form-control @error('program') is-invalid @enderror">
                            @error('program') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Major / specialisation</label>
                            <input type="text" wire:model="major" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            {{-- personal information --}}
            <div class="sec">
                <div class="head"><span>Personal information</span></div>
                <div class="body">
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" wire:model="address" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select wire:model="gender" class="form-select">
                                <option value="">—</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" wire:model="nationality" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" wire:model="birth_date"
                                   class="form-control @error('birth_date') is-invalid @enderror">
                            @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Birthplace</label>
                            <input type="text" wire:model="birthplace" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            {{-- admission data --}}
            <div class="sec">
                <div class="head"><span>Admission data</span></div>
                <div class="body">
                    <div class="d-flex gap-3 mb-3">
                        <label class="d-flex align-items-center gap-2" style="font-size:.8125rem">
                            <input type="radio" wire:model.live="admission_type" value="new" class="form-check-input mt-0"> A. New
                        </label>
                        <label class="d-flex align-items-center gap-2" style="font-size:.8125rem">
                            <input type="radio" wire:model.live="admission_type" value="transferee" class="form-check-input mt-0"> Transferee
                        </label>
                    </div>

                    @if ($admission_type === 'new')
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">School</label>
                                <input type="text" wire:model="adm_new_school" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" wire:model="adm_new_address" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Course</label>
                                <input type="text" wire:model="adm_new_course" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Year graduated</label>
                                <input type="text" wire:model="adm_new_year_graduated" class="form-control" placeholder="2019">
                            </div>
                        </div>
                    @else
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">School</label>
                                <input type="text" wire:model="adm_tr_school" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" wire:model="adm_tr_address" class="form-control">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Course</label>
                                <input type="text" wire:model="adm_tr_course" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year graduated</label>
                                <input type="text" wire:model="adm_tr_year_graduated" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Admission credential</label>
                                <input type="text" wire:model="adm_tr_credential" class="form-control"
                                       placeholder="Honorable Dismissal">
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">B. Date of admission</label>
                            <input type="date" wire:model="date_admitted"
                                   class="form-control @error('date_admitted') is-invalid @enderror">
                            @error('date_admitted') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- graduation data --}}
            <div class="sec">
                <div class="head"><span>Graduation data</span></div>
                <div class="body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Date conferred</label>
                            <input type="date" wire:model="date_conferred"
                                   class="form-control @error('date_conferred') is-invalid @enderror">
                            @error('date_conferred') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Board resolution no.</label>
                            <input type="text" wire:model="board_resolution_no" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" wire:model="board_resolution_date"
                                   class="form-control @error('board_resolution_date') is-invalid @enderror">
                            @error('board_resolution_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Awards</label>
                            <input type="text" wire:model="awards" class="form-control" placeholder="Cum Laude">
                        </div>
                    </div>
                </div>
            </div>

            {{-- subjects --}}
            <div class="sec">
                <div class="head">
                    <span>Subjects and ratings</span>
                    <span style="text-transform:none;letter-spacing:0;font-weight:400">
                        {{ count($rows) }} rows · {{ number_format($this->totalUnits, 1) }} units
                    </span>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="w-100 rowtab">
                            <thead>
                                <tr>
                                    <th style="width:19%">Term</th><th style="width:14%">Code</th>
                                    <th style="width:34%">Descriptive title</th><th style="width:9%">Final</th>
                                    <th style="width:9%">Removal</th><th style="width:9%">Units</th><th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    <tr wire:key="r-{{ $i }}">
                                        <td><input type="text" wire:model="rows.{{ $i }}.term" class="form-control" placeholder="First Sem 2023-2024"></td>
                                        <td><input type="text" wire:model="rows.{{ $i }}.code" class="form-control" placeholder="IT 101"></td>
                                        <td><input type="text" wire:model="rows.{{ $i }}.title" class="form-control"></td>
                                        <td><input type="text" wire:model="rows.{{ $i }}.grade" class="form-control text-center"></td>
                                        <td><input type="text" wire:model="rows.{{ $i }}.removal" class="form-control text-center"></td>
                                        <td><input type="text" wire:model="rows.{{ $i }}.units" class="form-control text-center"></td>
                                        <td class="text-end">
                                            <button wire:click="removeRow({{ $i }})" class="btn btn-psu-outline mini">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button wire:click="addRow" class="btn btn-psu-outline btn-sm mt-2">
                        <i class="bi bi-plus-lg"></i> Add a subject
                    </button>
                    @error('rows') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- other printed fields --}}
            <div class="sec">
                <div class="head"><span>Other printed fields</span></div>
                <div class="body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">NSTP serial no.</label>
                            <input type="text" wire:model="nstp_serial_no" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Program accreditation status</label>
                            <input type="text" wire:model="program_accreditation" class="form-control"
                                   placeholder="Level II Accredited, AACCUP">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Granted transfer credentials</label>
                            <input type="text" wire:model="granted_transfer_credentials" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <input type="text" wire:model="remarks" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of issue</label>
                            <input type="date" wire:model="issuedOn"
                                   class="form-control @error('issuedOn') is-invalid @enderror">
                            @error('issuedOn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="generate" class="btn btn-psu w-100 mb-4"
                    wire:loading.attr="disabled" wire:target="generate" @disabled(! $this->ready)>
                <span wire:loading.remove wire:target="generate">
                    <i class="bi bi-shield-lock"></i>
                    @if (! $this->ready) Enter the student number and name @else Generate, hash, and stamp the QR @endif
                </span>
                <span wire:loading wire:target="generate">
                    <span class="spinner-border spinner-border-sm me-1"></span> Building the transcript…
                </span>
            </button>
        </div>

        {{-- result panel --}}
        <div class="col-xl-4">
            @if ($this->issued)
                <div class="sec" wire:key="issued-{{ $this->issued->id }}">
                    <div class="head" style="color:#157a34"><span>Transcript issued</span></div>
                    <div class="body">
                        <div class="text-center mb-3">
                            <img src="{{ route('certificates.qr', $this->issued) }}" alt="QR code"
                                 style="width:130px;height:130px">
                            <div class="serial mt-2">{{ $this->issued->serial_number }}</div>
                        </div>
                        <div class="form-label">Fingerprint (SHA-256)</div>
                        <div class="hash-chip d-block mb-3">{{ $this->issued->content_hash }}</div>

                        <div class="form-label mb-2">Preview</div>
                        <div class="mb-3" style="height:320px;border:1px solid var(--line);
                                    border-radius:var(--radius);overflow:hidden;background:#eef1f6">
                            <iframe src="{{ route('certificates.print', $this->issued) }}#view=FitH&toolbar=0"
                                    style="width:100%;height:100%;border:0" title="Transcript preview"></iframe>
                        </div>

                        <div class="d-flex gap-2 mb-2">
                            <a href="{{ route('certificates.download', $this->issued) }}"
                               class="btn btn-psu flex-fill btn-sm"><i class="bi bi-download"></i> Download</a>
                            <a href="{{ route('certificates.print', $this->issued) }}" target="_blank"
                               class="btn btn-psu-outline flex-fill btn-sm"><i class="bi bi-printer"></i> Print</a>
                        </div>
                        <button wire:click="issueAnother" class="btn btn-psu-outline btn-sm w-100">
                            Issue another transcript
                        </button>
                    </div>
                </div>
            @else
                <div class="sec">
                    <div class="head"><span>How this works</span></div>
                    <div class="body">
                        <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
                            Search an existing student and every field fills from their record. For
                            someone not yet in the system, type the details — they are saved to the
                            record when you generate.
                        </p>
                        <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
                            That is the point of storing these fields: the second transcript for the
                            same student is a search and a click.
                        </p>
                        <p class="text-muted-celeste mb-0" style="font-size:.8125rem">
                            Everything entered is written into the hashed payload, so it is covered by
                            the fingerprint. A field left blank prints as empty space for handwriting.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
