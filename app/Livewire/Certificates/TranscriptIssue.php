<?php

namespace App\Livewire\Certificates;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Transcript of Records — manual entry, and batch import.
 *
 * Two modes on one screen. In "single" the staff type the details for one
 * student; in "import" they upload a spreadsheet covering many.
 *
 * Every field is stored on the student record, not only on the certificate.
 * That is the point of the exercise: once a student has been entered or
 * imported, issuing their next transcript is a matter of selecting them and
 * the form fills itself. Typing the same birthplace twice is the thing this
 * design exists to avoid.
 */
class TranscriptIssue extends Component
{
    use WithFileUploads;

    public string $mode = 'single';          // single | import

    // ---- student selection ----
    public string $search = '';
    public ?int $studentId = null;

    // ---- identity ----
    public string $student_number = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $suffix = '';
    public string $program = '';             // "Course" on the form
    public string $major = '';

    // ---- personal information ----
    public string $address = '';
    public string $gender = '';
    public string $nationality = 'Filipino';
    public string $birth_date = '';
    public string $birthplace = '';

    // ---- admission data ----
    public string $admission_type = 'new';   // new | transferee
    public string $adm_new_school = '';
    public string $adm_new_address = '';
    public string $adm_new_course = '';
    public string $adm_new_year_graduated = '';
    public string $adm_tr_school = '';
    public string $adm_tr_address = '';
    public string $adm_tr_course = '';
    public string $adm_tr_year_graduated = '';
    public string $adm_tr_credential = '';
    public string $date_admitted = '';       // B. Date of Admission

    // ---- graduation data ----
    public string $date_conferred = '';
    public string $board_resolution_no = '';
    public string $board_resolution_date = '';
    public string $awards = '';

    // ---- other printed fields ----
    public string $nstp_serial_no = '';
    public string $program_accreditation = '';
    public string $granted_transfer_credentials = '';
    public string $remarks = '';

    // ---- subject rows ----
    /** @var array<int, array<string, string>> */
    public array $rows = [];

    public string $issuedOn = '';
    public ?int $issuedId = null;

    // ---- import ----
    public $csv;
    public array $importRows = [];
    public array $importErrors = [];
    public array $importSummary = [];
    public array $importResult = [];

    /** Columns of the transcript import template, in order. */
    public const COLUMNS = [
        'student_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'email', 'program', 'major', 'address', 'gender', 'nationality',
        'birth_date', 'birthplace',
        'admission_type',
        'adm_new_school', 'adm_new_address', 'adm_new_course', 'adm_new_year_graduated',
        'adm_tr_school', 'adm_tr_address', 'adm_tr_course', 'adm_tr_year_graduated',
        'adm_tr_credential',
        'date_admitted',
        'date_conferred', 'board_resolution_no', 'board_resolution_date', 'awards',
        'nstp_serial_no', 'program_accreditation',
        'granted_transfer_credentials', 'remarks',
        'college', 'status',
    ];

    public const REQUIRED = ['student_number', 'first_name', 'last_name', 'program'];

    /** Fields carried between the record, the form, and the payload. */
    private const RECORD_FIELDS = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'program', 'major',
        'address', 'gender', 'nationality', 'birthplace',
        'admission_type',
        'adm_new_school', 'adm_new_address', 'adm_new_course', 'adm_new_year_graduated',
        'adm_tr_school', 'adm_tr_address', 'adm_tr_course', 'adm_tr_year_graduated',
        'adm_tr_credential',
        'board_resolution_no', 'awards', 'nstp_serial_no',
        'program_accreditation', 'granted_transfer_credentials', 'remarks',
    ];

    private const DATE_FIELDS = ['birth_date', 'date_admitted', 'date_conferred', 'board_resolution_date'];

    public function mount(): void
    {
        $this->issuedOn = now()->toDateString();
        $this->addRow();
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
        $this->resetErrorBag();
    }

    public function getResultsProperty()
    {
        if (strlen(trim($this->search)) < 2) {
            return collect();
        }

        $t = '%' . trim($this->search) . '%';

        return StudentRecord::query()
            ->where(fn ($q) => $q->where('student_number', 'ilike', $t)
                ->orWhere('first_name', 'ilike', $t)
                ->orWhere('last_name', 'ilike', $t))
            ->orderBy('last_name')->limit(8)->get();
    }

    public function getStudentProperty(): ?StudentRecord
    {
        return $this->studentId ? StudentRecord::find($this->studentId) : null;
    }

    public function getIssuedProperty(): ?Certificate
    {
        return $this->issuedId ? Certificate::find($this->issuedId) : null;
    }

    public function getTotalUnitsProperty(): float
    {
        return collect($this->rows)->sum(fn ($r) => (float) ($r['units'] ?? 0));
    }

    /**
     * Selecting a student fills the whole form from their record. Once a
     * student has been entered once, every later transcript is a click.
     */
    public function selectStudent(int $id): void
    {
        $record = StudentRecord::findOrFail($id);

        $this->studentId = $record->id;
        $this->search = '';
        $this->student_number = (string) $record->student_number;

        foreach (self::RECORD_FIELDS as $f) {
            $this->{$f} = (string) ($record->{$f} ?? '');
        }

        foreach (self::DATE_FIELDS as $f) {
            $v = $record->{$f};
            $this->{$f} = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : (string) ($v ?? '');
        }

        if ($this->nationality === '') {
            $this->nationality = 'Filipino';
        }

        if ($this->admission_type === '') {
            $this->admission_type = 'new';
        }

        $this->loadGrades($record);
    }

    public function clearStudent(): void
    {
        $this->studentId = null;
        $this->student_number = '';

        foreach (array_merge(self::RECORD_FIELDS, self::DATE_FIELDS) as $f) {
            $this->{$f} = '';
        }

        $this->nationality = 'Filipino';
        $this->admission_type = 'new';
        $this->rows = [];
        $this->addRow();
    }

    private function loadGrades(StudentRecord $record): void
    {
        $this->rows = [];

        foreach ((array) ($record->grades ?? []) as $g) {
            $this->rows[] = [
                'term'    => trim(($g['semester'] ?? '') . ' ' . ($g['academic_year'] ?? '')),
                'code'    => (string) ($g['code'] ?? ''),
                'title'   => (string) ($g['title'] ?? ''),
                'grade'   => (string) ($g['grade'] ?? ''),
                'removal' => (string) ($g['removal'] ?? ''),
                'units'   => (string) ($g['units'] ?? ''),
            ];
        }

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    public function addRow(): void
    {
        $last = end($this->rows) ?: [];

        // Carrying the term forward saves retyping it for every subject in a
        // semester, which is most of the work on this form.
        $this->rows[] = [
            'term' => $last['term'] ?? '',
            'code' => '', 'title' => '', 'grade' => '', 'removal' => '', 'units' => '',
        ];
    }

    public function removeRow(int $i): void
    {
        unset($this->rows[$i]);
        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    public function getReadyProperty(): bool
    {
        return trim($this->student_number) !== ''
            && trim($this->first_name) !== ''
            && trim($this->last_name) !== '';
    }

    // ═══════════════ single issuance ═══════════════

    public function generate(CertificateGenerator $generator): void
    {
        $this->validate([
            'student_number' => ['required', 'string', 'max:30'],
            'first_name'     => ['required', 'string', 'max:80'],
            'last_name'      => ['required', 'string', 'max:80'],
            'program'        => ['required', 'string', 'max:150'],
            'issuedOn'       => ['required', 'date'],
            'birth_date'     => ['nullable', 'date'],
            'date_admitted'  => ['nullable', 'date'],
            'date_conferred' => ['nullable', 'date'],
            'board_resolution_date' => ['nullable', 'date'],
            'rows'           => ['required', 'array', 'min:1'],
            'rows.*.units'   => ['nullable', 'numeric', 'between:0,20'],
        ], [
            'student_number.required' => 'The student number is printed on the transcript and links it to the student.',
            'program.required'        => 'The course is printed on the transcript.',
            'rows.required'           => 'A transcript needs at least one subject row.',
        ]);

        $rows = collect($this->rows)
            ->filter(fn ($r) => trim($r['code']) !== '' || trim($r['title']) !== '')
            ->values()->all();

        // Everything typed is written back to the student record, so the next
        // transcript for this student needs no retyping. This is the whole
        // reason the fields were added to the table.
        $record = $this->persistToRecord($rows);

            $certificate = $generator->issue($record, Certificate::TYPE_TOR, Auth::user(), [
            'issued_on' => $this->issuedOn,
            'payload'   => array_filter([
                'full_name'      => $this->composedName(),
                'student_number' => trim($this->student_number),
                'program'        => $this->program,
                'major'          => $this->major ?: null,
                'address'        => $this->address ?: null,
                'gender'         => $this->gender ?: null,
                'nationality'    => $this->nationality ?: null,
                'birth_date'     => $this->birth_date ?: null,
                'birthplace'     => $this->birthplace ?: null,
                'admission_type' => $this->admission_type,
                'adm_new_school'         => $this->adm_new_school ?: null,
                'adm_new_address'        => $this->adm_new_address ?: null,
                'adm_new_course'         => $this->adm_new_course ?: null,
                'adm_new_year_graduated' => $this->adm_new_year_graduated ?: null,
                'adm_tr_school'          => $this->adm_tr_school ?: null,
                'adm_tr_address'         => $this->adm_tr_address ?: null,
                'adm_tr_course'          => $this->adm_tr_course ?: null,
                'adm_tr_year_graduated'  => $this->adm_tr_year_graduated ?: null,
                'adm_tr_credential'      => $this->adm_tr_credential ?: null,
                'date_admitted'          => $this->date_admitted ?: null,
                'date_conferred'         => $this->date_conferred ?: null,
                'board_resolution_no'    => $this->board_resolution_no ?: null,
                'board_resolution_date'  => $this->board_resolution_date ?: null,
                'awards'                 => $this->awards ?: null,
                'nstp_serial_no'         => $this->nstp_serial_no ?: null,
                'program_accreditation'  => $this->program_accreditation ?: null,
                'granted_transfer_credentials' => $this->granted_transfer_credentials ?: null,
                'remarks'     => $this->remarks ?: null,
                'grades'      => $rows,
                'total_units' => $this->totalUnits,
            ], fn ($v) => $v !== null),
        ]);
        $this->issuedId = $certificate->id;

        session()->flash('tor-status',
            "Transcript {$certificate->serial_number} issued for {$this->composedName()}. "
            . 'The details are saved to the student record.');
    }

    private function persistToRecord(array $rows): StudentRecord
    {
        $values = ['grades' => $rows];

        foreach (self::RECORD_FIELDS as $f) {
            $values[$f] = trim((string) $this->{$f}) ?: null;
        }

        foreach (self::DATE_FIELDS as $f) {
            $values[$f] = $this->{$f} ?: null;
        }

        $record = StudentRecord::firstOrNew(['student_number' => trim($this->student_number)]);
        $isNew = ! $record->exists;

        // A record created here needs the columns the rest of the system
        // expects, which the transcript form does not ask for.
        if ($isNew) {
            $record->college = $record->college ?: 'Unassigned';
            $record->status = $record->status ?: 'graduated';
        }

        $record->fill($values)->save();

        AuditLog::record($isNew ? 'student.created' : 'student.updated', $record, [
            'student_number' => $record->student_number,
            'via'            => 'transcript issuance',
            'by'             => Auth::user()?->email,
        ]);

        return $record->refresh();
    }

    private function composedName(): string
    {
        return trim(implode(' ', array_filter([
            trim($this->first_name), trim($this->middle_name),
            trim($this->last_name), trim($this->suffix),
        ])));
    }

    public function issueAnother(): void
    {
        $this->reset();
        $this->mount();
    }

    // ═══════════════ batch import ═══════════════

    public function downloadTemplate(): StreamedResponse
    {
        $domain = config('celeste.institution.email_domain', 'parsu.edu.ph');

        $sample = [
            '2021-00184', 'Juan', 'Dela Cruz', 'Dela Cruz', '',
            "jdelacruz184.pbox@{$domain}",
            'Bachelor of Science in Information Technology', '',
            'Goa, Camarines Sur', 'Male', 'Filipino', '2003-05-14', 'Goa, Camarines Sur',
            'new',
            'Goa National High School', 'Goa, Camarines Sur', 'Junior High School', '2019',
            '', '', '', '', '',
            '2019-08-12',
            '2023-06-15', 'BOR Res. No. 2023-045', '2023-05-30', 'Cum Laude',
            'NSTP-2019-00184', 'Level II Accredited, AACCUP',
            '', 'Transcript closed.',
            'College of Engineering and Computational Sciences', 'graduated',
        ];

        return response()->streamDownload(function () use ($sample) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // Excel needs this to read UTF-8
            fputcsv($out, self::COLUMNS);
            fputcsv($out, $sample);
            fclose($out);
        }, 'celeste-transcript-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function updatedCsv(): void
    {
        $this->reset(['importRows', 'importErrors', 'importSummary', 'importResult']);
    }

    public function checkImport(): void
    {
        $this->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'csv.mimes' => 'That is not a CSV file. In Excel use File → Save As → CSV.',
        ]);

        $this->reset(['importRows', 'importErrors', 'importSummary', 'importResult']);

        $handle = fopen($this->csv->getRealPath(), 'r');
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', (string) fgets($handle));
        $headers = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(trim($headerLine)));

        $missing = array_diff(self::REQUIRED, $headers);

        if ($missing !== []) {
            fclose($handle);
            $this->importErrors[] = ['line' => 1,
                'message' => 'Missing columns: ' . implode(', ', $missing)
                    . '. Download the template and copy your data into it.'];

            return;
        }

        $colleges = array_values((array) config('celeste.colleges', []));
        $seen = []; $line = 1; $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($data, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $h) {
                if (in_array($h, self::COLUMNS, true)) {
                    $row[$h] = trim((string) ($data[$i] ?? ''));
                }
            }

            foreach (self::REQUIRED as $f) {
                if (($row[$f] ?? '') === '') {
                    $this->importErrors[] = ['line' => $line, 'message' => "The {$f} column is empty."];
                }
            }

            $type = strtolower($row['admission_type'] ?? 'new');
            if ($type !== '' && ! in_array($type, ['new', 'transferee'], true)) {
                $this->importErrors[] = ['line' => $line,
                    'message' => "admission_type \"{$type}\" must be new or transferee."];
            }
            $row['admission_type'] = $type ?: 'new';

            if (($row['college'] ?? '') !== '' && ! in_array($row['college'], $colleges, true)) {
                $this->importErrors[] = ['line' => $line,
                    'message' => "College \"{$row['college']}\" is not on the list. Check the spelling."];
            }

            foreach (['birth_date', 'date_admitted', 'date_conferred', 'board_resolution_date'] as $d) {
                if (($row[$d] ?? '') !== '' && strtotime($row[$d]) === false) {
                    $this->importErrors[] = ['line' => $line,
                        'message' => "{$d} \"{$row[$d]}\" is not a date. Use YYYY-MM-DD."];
                }
            }

            $num = $row['student_number'] ?? '';
            if ($num !== '' && isset($seen[$num])) {
                $this->importErrors[] = ['line' => $line,
                    'message' => "Student number {$num} also appears on line {$seen[$num]}."];
            }
            $seen[$num] = $line;

            $rows[] = $row;
        }

        fclose($handle);

        $this->importRows = $rows;

        $numbers = array_column($rows, 'student_number');
        $existing = StudentRecord::whereIn('student_number', $numbers)->pluck('student_number')->all();

        $this->importSummary = [
            'total'    => count($rows),
            'new'      => count(array_diff($numbers, $existing)),
            'existing' => count(array_intersect($numbers, $existing)),
            'errors'   => count($this->importErrors),
        ];
    }

    public function runImport(): void
    {
        if ($this->importRows === [] || $this->importErrors !== []) {
            return;
        }

        $created = 0; $updated = 0;

        foreach ($this->importRows as $row) {
            $values = array_filter($row, fn ($v) => $v !== '' && $v !== null);
            unset($values['student_number']);

            $record = StudentRecord::firstOrNew(['student_number' => $row['student_number']]);
            $isNew = ! $record->exists;

            if ($isNew) {
                $record->college = $values['college'] ?? 'Unassigned';
                $record->status  = $values['status'] ?? 'graduated';
            }

            $record->fill($values)->save();

            $isNew ? $created++ : $updated++;
        }

        AuditLog::record('students.imported', null, [
            'file' => $this->csv->getClientOriginalName(),
            'purpose' => 'transcript data',
            'created' => $created, 'updated' => $updated,
            'by' => Auth::user()?->email,
        ]);

        $this->importResult = compact('created', 'updated');
        $this->reset(['csv', 'importRows', 'importErrors', 'importSummary']);
    }

    public function startOverImport(): void
    {
        $this->reset(['csv', 'importRows', 'importErrors', 'importSummary', 'importResult']);
    }

    public function render()
    {
        return view('livewire.certificates.transcript-issue');
    }
}
