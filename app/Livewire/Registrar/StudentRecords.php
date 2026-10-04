<?php

namespace App\Livewire\Registrar;

use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Student Records — add, edit and remove the records documents are drawn from.
 *
 * Writes go straight to the configured database, which in this deployment is
 * PostgreSQL on Supabase. Nothing is held between steps: the form saves on
 * submit and the list re-reads, so two staff working at once see each other's
 * changes on their next action rather than overwriting them.
 */
class StudentRecords extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $pendingDeleteId = null;

    /**
     * Messages are plain properties rendered by Blade, not browser-side
     * state. Livewire 3 ships its own Alpine; a page that also loads Alpine
     * from a CDN ends up with two copies, the second refuses to start, and
     * every directive on the page quietly stops working. A banner that
     * depends on Alpine then shows itself empty and never clears.
     */
    public ?string $flash = null;
    public ?string $blocked = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /**
     * Ticked when the student lives where they are registered, which is the
     * common case. The temporary address is still written to its own
     * columns rather than left null: a report that asks "where do our
     * students actually live" should not have to know about this checkbox.
     */
    public bool $sameAddress = false;

    // ── CSV import ──────────────────────────────────────────────────
    public bool $showImport = false;
    public $csv = null;
    public string $onDuplicate = 'skip';   // skip | update

    /** Summary of the uploaded file, shown before anything is written. */
    public ?array $preview = null;

    public function mount(): void
    {
        $this->form = $this->blankForm();
    }

    // ------------------------------------------------------------------
    // Reference lists
    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // Philippine address lookups
    //
    // student_records stores place NAMES, not PSGC codes, because that is
    // what the columns already held and what the documents print. The codes
    // are used only to walk the hierarchy while the form is open, so each
    // level resolves the one above it by name.
    // ------------------------------------------------------------------

    #[Computed]
    public function provinces(): array
    {
        return DB::table('ph_provinces')->orderBy('name')->pluck('name')->all();
    }

    protected function provinceCode(string $name): ?string
    {
        if ($name === '') {
            return null;
        }

        return DB::table('ph_provinces')->where('name', $name)->value('code');
    }

    protected function cityCode(string $provinceName, string $cityName): ?string
    {
        $provinceCode = $this->provinceCode($provinceName);

        if (! $provinceCode || $cityName === '') {
            return null;
        }

        return DB::table('ph_cities')
            ->where('province_code', $provinceCode)
            ->where('name', $cityName)
            ->value('code');
    }

    public function citiesIn(string $provinceName): array
    {
        $code = $this->provinceCode($provinceName);

        if (! $code) {
            return [];
        }

        return DB::table('ph_cities')
            ->where('province_code', $code)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function barangaysIn(string $provinceName, string $cityName): array
    {
        $code = $this->cityCode($provinceName, $cityName);

        if (! $code) {
            return [];
        }

        return DB::table('ph_barangays')
            ->where('city_code', $code)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * As with college and programme: a value already on the record that is
     * no longer in the reference data stays selectable, so an old address
     * is not silently blanked by a later PSGC release.
     */
    public function addressOptions(string $prefix, string $level): array
    {
        $province = (string) ($this->form[$prefix . '_province'] ?? '');
        $city     = (string) ($this->form[$prefix . '_city'] ?? '');

        $list = match ($level) {
            'province' => $this->provinces(),
            'city'     => $this->citiesIn($province),
            'barangay' => $this->barangaysIn($province, $city),
            default    => [],
        };

        return $this->withCurrent($list, (string) ($this->form[$prefix . '_' . $level] ?? ''));
    }

    protected array $addressParts = ['province', 'city', 'barangay', 'zip'];

    protected function copyPermanentToTemporary(): void
    {
        foreach ($this->addressParts as $part) {
            $this->form['temp_' . $part] = $this->form['perm_' . $part] ?? '';
        }
    }

    protected function clearTemporary(): void
    {
        foreach ($this->addressParts as $part) {
            $this->form['temp_' . $part] = '';
        }
    }

    /**
     * True when the two addresses already match on a record being edited,
     * so the checkbox comes up ticked rather than making the registrar
     * re-establish something the data already says.
     */
    protected function temporaryMatchesPermanent(): bool
    {
        $permanent = array_map(
            fn ($part) => trim((string) ($this->form['perm_' . $part] ?? '')),
            $this->addressParts,
        );

        if (implode('', $permanent) === '') {
            return false;
        }

        foreach ($this->addressParts as $i => $part) {
            if (trim((string) ($this->form['temp_' . $part] ?? '')) !== $permanent[$i]) {
                return false;
            }
        }

        return true;
    }

    public function updatedSameAddress(bool $value): void
    {
        $value ? $this->copyPermanentToTemporary() : $this->clearTemporary();
    }

    public function civilStatuses(): array
    {
        return ['Single', 'Married', 'Widowed', 'Separated', 'Annulled'];
    }

    public function colleges(): array
    {
        return config('celeste.colleges', []);
    }

    /**
     * Programmes offered by the college currently chosen on the form.
     *
     * config('celeste.programs') is keyed by college name, and those names
     * contain hyphens and spaces, so the array is read directly rather than
     * through dot notation.
     */
    public function programsForCollege(): array
    {
        $college = $this->form['college'] ?? '';

        return config('celeste.programs', [])[$college] ?? [];
    }

    /**
     * The lists the selects actually render.
     *
     * A record saved before a college or programme was renamed would hold a
     * value no longer on the list. Dropping it would silently blank the
     * field on the next save, so the stored value is kept as an option and
     * accepted by validation. The registrar can change it deliberately.
     */
    public function collegeOptions(): array
    {
        return $this->withCurrent($this->colleges(), $this->form['college'] ?? '');
    }

    public function programOptions(): array
    {
        return $this->withCurrent($this->programsForCollege(), $this->form['program'] ?? '');
    }

    protected function withCurrent(array $list, string $current): array
    {
        if ($current !== '' && ! in_array($current, $list, true)) {
            array_unshift($list, $current);
        }

        return $list;
    }

    public function yearLevels(): array
    {
        return config('celeste.academics.year_levels', []);
    }

    public function sections(): array
    {
        return config('celeste.academics.sections', []);
    }

    public function statuses(): array
    {
        return [
            'enrolled'    => 'Enrolled',
            'graduated'   => 'Graduated',
            'transferred' => 'Transferred',
            'irregular'   => 'Irregular'
        ];
    }

    // ------------------------------------------------------------------
    // Listing
    // ------------------------------------------------------------------

    /**
     * Only the columns the table renders are selected. The record carries
     * forty odd fields and none of the rest appear here, so fetching them
     * costs round trips for nothing.
     */
    #[Computed]
    public function records()
    {
        return StudentRecord::query()
            ->select([
                'id', 'student_number', 'first_name', 'middle_name',
                'last_name', 'suffix', 'college', 'program',
                'year_level', 'section', 'status',
            ])
            ->withCount('certificates')
            ->when($this->statusFilter !== '',
                fn ($q) => $q->where('status', $this->statusFilter))
            ->when(strlen(trim($this->search)) >= 2, function ($q) {
                $term = trim($this->search);
                // Prefix matches so the index can be used. A leading wildcard
                // would force a sequential scan of the whole table.
                $q->where(function ($w) use ($term) {
                    $w->where('student_number', 'ilike', $term . '%')
                      ->orWhere('last_name', 'ilike', $term . '%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    /**
     * Changing the college invalidates the programme beneath it: a BSIT
     * student does not remain BSIT under the College of Education. Clearing
     * it forces a deliberate choice rather than leaving a mismatched pair.
     */
    public function updated(string $name): void
    {
        if ($name === 'form.college') {
            $this->form['program'] = '';
        }

        if ($name === 'form.admission_type') {
            $this->clearUnusedAdmissionFields();
        }

        // Changing a province invalidates the city beneath it, and the
        // barangay beneath that. Leaving them would record a barangay that
        // does not exist in the chosen city.
        foreach (['perm', 'temp'] as $prefix) {
            if ($name === "form.{$prefix}_province") {
                $this->form[$prefix . '_city'] = '';
                $this->form[$prefix . '_barangay'] = '';
            }

            if ($name === "form.{$prefix}_city") {
                $this->form[$prefix . '_barangay'] = '';
            }
        }

        // With the box ticked the temporary address is a mirror, so it has
        // to follow any later edit to the permanent one.
        if ($this->sameAddress && str_starts_with($name, 'form.perm_')) {
            $this->copyPermanentToTemporary();
        }
    }

    /**
     * Only one admission history applies to a student.
     *
     * Switching the type hides the other block, and anything already typed
     * there has to go with it -- otherwise a transferee's details stay in
     * the record, invisible on the form, and print on a document later.
     * Fired from the updated hook, so it runs when the registrar changes
     * the type and not when an existing record is loaded for editing.
     */
    protected function clearUnusedAdmissionFields(): void
    {
        $newStudent = [
            'adm_new_school', 'adm_new_address',
            'adm_new_course', 'adm_new_year_graduated',
        ];

        $transferee = [
            'adm_tr_school', 'adm_tr_address', 'adm_tr_course',
            'adm_tr_year_graduated', 'adm_tr_credential',
        ];

        $clear = match ($this->form['admission_type'] ?? '') {
            'New'        => $transferee,
            'Transferee' => $newStudent,
            default      => array_merge($newStudent, $transferee),
        };

        foreach ($clear as $field) {
            $this->form[$field] = '';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------------
    // Form
    // ------------------------------------------------------------------

    protected function blankForm(): array
    {
        return [
            // identity
            'student_number' => '', 'last_name' => '', 'first_name' => '',
            'middle_name' => '', 'suffix' => '', 'gender' => '',
            'civil_status' => '', 'birth_date' => '', 'birthplace' => '',
            'nationality' => '', 'religion' => '',
            'email' => '', 'contact_number' => '',

            // permanent address
            'perm_province' => '', 'perm_city' => '',
            'perm_barangay' => '', 'perm_zip' => '',

            // temporary address
            'temp_province' => '', 'temp_city' => '',
            'temp_barangay' => '', 'temp_zip' => '',

            // academic
            'college' => '', 'program' => '', 'major' => '',
            'year_level' => '', 'section' => '', 'academic_year' => '',
            'semester' => '', 'status' => 'enrolled',

            // admission
            'admission_type' => '', 'date_admitted' => '',
            'adm_new_school' => '', 'adm_new_address' => '',
            'adm_new_course' => '', 'adm_new_year_graduated' => '',
            'adm_tr_school' => '', 'adm_tr_address' => '',
            'adm_tr_course' => '', 'adm_tr_year_graduated' => '',
            'adm_tr_credential' => '',

            // graduation
            'date_graduated' => '', 'date_conferred' => '',
            'board_resolution_no' => '', 'board_resolution_date' => '',
            'latin_honor' => '', 'awards' => '',
            'general_weighted_average' => '',

            // other
            'nstp_serial_no' => '', 'program_accreditation' => '',
            'granted_transfer_credentials' => '', 'remarks' => '',
        ];
    }

    public function rules(): array
    {
        return [
            'form.student_number' => [
                'required', 'string', 'max:20',
                // A student number identifies one person. Ignoring the row
                // being edited lets a record be saved without changing it.
                Rule::unique('student_records', 'student_number')
                    ->ignore($this->editingId),
            ],
            'form.last_name'  => ['required', 'string', 'max:100'],
            'form.first_name' => ['required', 'string', 'max:100'],
            'form.college'    => ['required', Rule::in($this->collegeOptions())],
            'form.program'    => ['required', Rule::in($this->programOptions())],
            'form.status'     => ['required', Rule::in(array_keys($this->statuses()))],

            'form.year_level' => ['nullable', Rule::in($this->yearLevels())],
            'form.section'    => ['nullable', Rule::in($this->sections())],

            'form.email'       => ['nullable', 'email', 'max:255'],
            'form.middle_name' => ['nullable', 'string', 'max:100'],
            'form.suffix'      => ['nullable', 'string', 'max:20'],
            'form.gender'      => ['nullable', 'string', 'max:20'],
            'form.nationality' => ['nullable', 'string', 'max:50'],
            'form.birthplace'  => ['nullable', 'string', 'max:150'],
            'form.religion'    => ['nullable', 'string', 'max:100'],

            // Loose on purpose. Numbers get written 0930 241 8587, +63 930...,
            // and with extensions; a strict pattern rejects real entries and
            // teaches staff to put the number somewhere else.
            'form.contact_number' => ['nullable', 'string', 'max:30'],

            'form.civil_status' => ['nullable', Rule::in($this->civilStatuses())],

            'form.perm_province' => ['nullable', Rule::in($this->addressOptions('perm', 'province'))],
            'form.perm_city'     => ['nullable', Rule::in($this->addressOptions('perm', 'city'))],
            'form.perm_barangay' => ['nullable', Rule::in($this->addressOptions('perm', 'barangay'))],
            'form.perm_zip'      => ['nullable', 'string', 'max:10'],

            'form.temp_province' => ['nullable', Rule::in($this->addressOptions('temp', 'province'))],
            'form.temp_city'     => ['nullable', Rule::in($this->addressOptions('temp', 'city'))],
            'form.temp_barangay' => ['nullable', Rule::in($this->addressOptions('temp', 'barangay'))],
            'form.temp_zip'      => ['nullable', 'string', 'max:10'],

            'form.major'       => ['nullable', 'string', 'max:150'],

            'form.academic_year' => ['nullable', 'string', 'max:20'],
            'form.semester'      => ['nullable', 'string', 'max:20'],

            'form.birth_date'            => ['nullable', 'date'],
            'form.date_admitted'         => ['nullable', 'date'],
            'form.date_graduated'        => ['nullable', 'date', 'after_or_equal:form.date_admitted'],
            'form.date_conferred'        => ['nullable', 'date'],
            'form.board_resolution_date' => ['nullable', 'date'],

            'form.general_weighted_average' => ['nullable', 'numeric'],

            'form.admission_type'         => ['nullable', 'string', 'max:50'],
            'form.adm_new_school'         => ['nullable', 'string', 'max:150'],
            'form.adm_new_address'        => ['nullable', 'string', 'max:255'],
            'form.adm_new_course'         => ['nullable', 'string', 'max:150'],
            'form.adm_new_year_graduated' => ['nullable', 'string', 'max:20'],
            'form.adm_tr_school'          => ['nullable', 'string', 'max:150'],
            'form.adm_tr_address'         => ['nullable', 'string', 'max:255'],
            'form.adm_tr_course'          => ['nullable', 'string', 'max:150'],
            'form.adm_tr_year_graduated'  => ['nullable', 'string', 'max:20'],
            'form.adm_tr_credential'      => ['nullable', 'string', 'max:150'],

            'form.latin_honor'                  => ['nullable', 'string', 'max:50'],
            'form.awards'                       => ['nullable', 'string', 'max:255'],
            'form.nstp_serial_no'               => ['nullable', 'string', 'max:50'],
            'form.board_resolution_no'          => ['nullable', 'string', 'max:100'],
            'form.program_accreditation'        => ['nullable', 'string', 'max:150'],
            'form.granted_transfer_credentials' => ['nullable', 'string'],
            'form.remarks'                      => ['nullable', 'string'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.student_number' => 'student number',
            'form.last_name'      => 'last name',
            'form.first_name'     => 'first name',
            'form.college'        => 'college',
            'form.program'        => 'program',
            'form.year_level'     => 'year level',
            'form.section'        => 'section',
            'form.date_graduated' => 'date graduated',
        ];
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    public function create(): void
    {
        $this->clearMessages();
        $this->resetValidation();
        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->sameAddress = false;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $record = StudentRecord::findOrFail($id);

        $this->clearMessages();
        $this->resetValidation();
        $this->editingId = $id;

        $form = $this->blankForm();
        foreach (array_keys($form) as $field) {
            $value = $record->{$field};

            // Date casts come back as Carbon; a date input wants Y-m-d.
            $form[$field] = $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : (string) ($value ?? '');
        }

        $this->form = $form;
        $this->sameAddress = $this->temporaryMatchesPermanent();
        $this->showForm = true;
    }

    public function save(): void
    {
        // Re-copied here as well as on change: the permanent address may
        // have been edited after the box was ticked, and the stored record
        // should match what the form showed.
        if ($this->sameAddress) {
            $this->copyPermanentToTemporary();
        }

        $this->validate();

        // Empty strings are not the same as "no value". Writing '' into a
        // nullable date column fails outright, and '' in a nullable text
        // column makes a template print a blank line where it should print
        // nothing at all.
        $data = collect($this->form)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();

        // The Transfer Credential prints a one-line address from the hashed
        // payload, so the `address` column still has to hold one. It is built
        // from the permanent address parts rather than typed twice, which
        // keeps the two from disagreeing.
        $data['address'] = $this->composedAddress() ?: null;

        if ($this->editingId) {
            StudentRecord::findOrFail($this->editingId)->update($data);
            $message = 'Record updated.';
        } else {
            $record = StudentRecord::create($data);
            $this->editingId = $record->id;
            $message = 'Record added.';
        }

        $this->showForm = false;
        unset($this->records);

        $this->flash = $message;
        $this->blocked = null;
    }

    /**
     * Barangay, City, Province ZIP -- skipping whatever is blank, so a
     * half-filled address does not come out as ", , 4422".
     */
    protected function composedAddress(): string
    {
        $parts = array_filter([
            trim((string) ($this->form['perm_barangay'] ?? '')),
            trim((string) ($this->form['perm_city'] ?? '')),
            trim((string) ($this->form['perm_province'] ?? '')),
        ], fn ($p) => $p !== '');

        $line = implode(', ', $parts);
        $zip  = trim((string) ($this->form['perm_zip'] ?? ''));

        return trim($line . ($zip !== '' ? ' ' . $zip : ''));
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    // ------------------------------------------------------------------
    // Deletion
    // ------------------------------------------------------------------

    /**
     * A record with issued documents cannot be deleted.
     *
     * Those documents are in circulation and each verifies against this
     * student's certificate rows. Removing the record would leave every QR
     * code already printed resolving to nothing, which a verifier reads as
     * forgery rather than as an administrative tidy-up. Where a student
     * should no longer appear, the status field is the right instrument.
     */
    public function confirmDelete(int $id): void
    {
        $issued = DB::table('certificates')->where('student_record_id', $id)->count();

        if ($issued > 0) {
            $this->flash = null;
            $this->blocked = "This student has {$issued} issued document"
                . ($issued === 1 ? '' : 's')
                . '. Deleting the record would break verification for documents '
                . 'already released. Revoke those documents first, or change the '
                . "student's status instead.";
            return;
        }

        $this->pendingDeleteId = $id;
    }

    public function delete(): void
    {
        if (! $this->pendingDeleteId) {
            return;
        }

        // Checked again at the point of deletion: a document may have been
        // issued between the prompt appearing and the click.
        $issued = DB::table('certificates')
            ->where('student_record_id', $this->pendingDeleteId)
            ->count();

        if ($issued > 0) {
            $this->pendingDeleteId = null;
            $this->flash = null;
            $this->blocked = 'A document was issued for this student while the '
                . 'prompt was open. The record was not deleted.';
            return;
        }

        StudentRecord::whereKey($this->pendingDeleteId)->delete();

        $this->pendingDeleteId = null;
        unset($this->records);

        $this->flash = 'Record deleted.';
        $this->blocked = null;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
    }

    public function clearMessages(): void
    {
        $this->flash = null;
        $this->blocked = null;
    }

    // ------------------------------------------------------------------
    // CSV import
    //
    // Two passes over the file: one to report what will happen, one to do
    // it. Writing several hundred records is not something to discover was
    // wrong afterwards, so nothing is inserted until the registrar has seen
    // the counts and the rejected rows.
    // ------------------------------------------------------------------

    /** Columns a CSV may set. Anything else in the header is ignored. */
    protected function importable(): array
    {
        return array_keys($this->blankForm());
    }

    public function openImport(): void
    {
        $this->clearMessages();
        $this->reset(['csv', 'preview']);
        $this->onDuplicate = 'skip';
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->reset(['csv', 'preview', 'showImport']);
    }

    public function updatedCsv(): void
    {
        $this->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ], [], ['csv' => 'CSV file']);

        $this->preview = $this->readCsv($this->csv->getRealPath());
    }

    /**
     * Parse and check, without touching the database beyond the duplicate
     * lookup. Returns counts plus the rejected rows with their reasons.
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['fatal' => 'The file could not be opened.'];
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            return ['fatal' => 'The file is empty.'];
        }

        // Excel writes a byte order mark ahead of the first header, which
        // would otherwise make "student_number" arrive as "\u{FEFF}student_number".
        $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $known = $this->importable();
        $recognised = array_values(array_intersect($header, $known));

        if (! in_array('student_number', $recognised, true)) {
            fclose($handle);
            return ['fatal' => 'No student_number column found. The first row '
                . 'of the file must be the column headings.'];
        }

        $existing = StudentRecord::query()->pluck('id', 'student_number')->all();

        $rows    = [];
        $errors  = [];
        $seen    = [];
        $line    = 1;
        $updates = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;   // blank line
            }

            $row = $this->mapRow($header, $data, $known);
            $number = (string) ($row['student_number'] ?? '');

            if ($number !== '' && isset($seen[$number])) {
                $errors[] = ['line' => $line, 'number' => $number,
                             'why' => 'Repeated in this file (first seen on line '
                                      . $seen[$number] . ').'];
                continue;
            }

            $fails = $this->rowErrors($row);

            if ($fails !== []) {
                $errors[] = ['line' => $line, 'number' => $number,
                             'why' => implode(' ', $fails)];
                continue;
            }

            $isExisting = isset($existing[$number]);

            if ($isExisting && $this->onDuplicate === 'skip') {
                $errors[] = ['line' => $line, 'number' => $number,
                             'why' => 'Already on file. Skipped.'];
                continue;
            }

            if ($isExisting) {
                $updates++;
            }

            $seen[$number] = $line;
            $rows[] = $row;
        }

        fclose($handle);

        return [
            'ready'      => count($rows),
            'new'        => count($rows) - $updates,
            'updates'    => $updates,
            'rejected'   => count($errors),
            'errors'     => array_slice($errors, 0, 50),
            'truncated'  => max(0, count($errors) - 50),
            'ignored'    => array_values(array_diff($header, $known)),
            'rows'       => $rows,
        ];
    }

    /**
     * One CSV line to a record, keeping only recognised columns and
     * normalising the values the database is fussy about.
     */
    protected function mapRow(array $header, array $data, array $known): array
    {
        $row = [];

        foreach ($header as $i => $column) {
            if (! in_array($column, $known, true)) {
                continue;
            }

            $value = trim((string) ($data[$i] ?? ''));
            $row[$column] = $value === '' ? null : $value;
        }

        // Dates: Excel writes 3/14/2005 as readily as 2005-03-14, and
        // Postgres accepts only the second.
        foreach (['birth_date', 'date_admitted', 'date_graduated',
                  'date_conferred', 'board_resolution_date'] as $field) {
            if (! empty($row[$field])) {
                try {
                    $row[$field] = Carbon::parse($row[$field])->format('Y-m-d');
                } catch (\Throwable) {
                    // Left as-is; rowErrors reports it.
                }
            }
        }

        if (! empty($row['status'])) {
            $row['status'] = strtolower($row['status']);
        }

        // The single-line address the Transfer Credential prints is composed
        // here too, so imported records are not missing it.
        $parts = array_filter([
            $row['perm_barangay'] ?? null,
            $row['perm_city'] ?? null,
            $row['perm_province'] ?? null,
        ]);

        $line = implode(', ', $parts);
        $zip  = $row['perm_zip'] ?? '';
        $row['address'] = trim($line . ($zip ? ' ' . $zip : '')) ?: null;

        return $row;
    }

    /** @return array<int, string> */
    protected function rowErrors(array $row): array
    {
        $validator = Validator::make($row, [
            'student_number' => ['required', 'string', 'max:20'],
            'last_name'      => ['required', 'string', 'max:100'],
            'first_name'     => ['required', 'string', 'max:100'],
            'college'        => ['required', 'string', 'max:150'],
            'program'        => ['required', 'string', 'max:150'],
            'status'         => ['required', Rule::in(array_keys($this->statuses()))],
            'email'          => ['nullable', 'email', 'max:255'],
            'birth_date'     => ['nullable', 'date_format:Y-m-d'],
            'date_admitted'  => ['nullable', 'date_format:Y-m-d'],
            'date_graduated' => ['nullable', 'date_format:Y-m-d'],
            'year_level'     => ['nullable', Rule::in($this->yearLevels())],
            'section'        => ['nullable', Rule::in($this->sections())],
        ]);

        $messages = [];

        foreach ($validator->errors()->all() as $message) {
            $messages[] = $message;
        }

        // Warned about rather than rejected: the record is still usable, and
        // refusing the whole row over a renamed college helps nobody.
        if (! empty($row['college']) && ! in_array($row['college'], $this->colleges(), true)) {
            $messages[] = 'College is not one of the configured names.';
        }

        return $messages;
    }

    public function updatedOnDuplicate(): void
    {
        if ($this->csv) {
            $this->preview = $this->readCsv($this->csv->getRealPath());
        }
    }

    public function import(): void
    {
        if (! $this->preview || empty($this->preview['rows'])) {
            return;
        }

        $rows = $this->preview['rows'];
        $new = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, &$new, &$updated) {
            foreach (array_chunk($rows, 200) as $chunk) {
                foreach ($chunk as $row) {
                    $record = StudentRecord::where(
                        'student_number', $row['student_number']
                    )->first();

                    if ($record) {
                        $record->update($row);
                        $updated++;
                    } else {
                        StudentRecord::create($row);
                        $new++;
                    }
                }
            }
        });

        $this->closeImport();
        unset($this->records);

        $this->flash = "Import finished. {$new} added"
            . ($updated ? ", {$updated} updated" : '') . '.';
        $this->blocked = null;
    }

    // ------------------------------------------------------------------

    public function render()
    {
        return view('livewire.registrar.student-records');
    }
}