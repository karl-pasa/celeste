<?php

namespace App\Livewire\Students;

use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Student Records — add, edit and remove the records documents are drawn from.
 *
 * Saves go straight to the configured database, which in this deployment is
 * PostgreSQL on Supabase. Nothing is held in session between steps: the form
 * writes on submit and the list re-reads, so two staff working at once see
 * each other's changes on their next action rather than overwriting them.
 */
class ManageRecords extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $pendingDeleteId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(): void
    {
        $this->form = $this->blankForm();
    }

    // ------------------------------------------------------------------
    // Reference lists
    // ------------------------------------------------------------------

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
        return ['enrolled' => 'Enrolled',
                'graduated' => 'Graduated',
                'transferred' => 'Transferred'];
    }

    // ------------------------------------------------------------------
    // Listing
    // ------------------------------------------------------------------

    /**
     * Only the columns the table shows are selected. The record carries forty
     * odd fields and none of the rest are rendered here, so fetching them
     * costs round trips for nothing.
     */
    #[Computed]
    public function records()
    {
        return StudentRecord::query()
            ->select([
                'id', 'student_number', 'last_name', 'first_name',
                'middle_name', 'suffix', 'program', 'year_level', 'section',
                'status',
            ])
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
            'birth_date' => '', 'birthplace' => '', 'nationality' => '',
            'address' => '', 'email' => '',

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
            'form.college'    => ['required', 'string', 'max:150'],
            'form.program'    => ['required', 'string', 'max:150'],
            'form.status'     => ['required', Rule::in(array_keys($this->statuses()))],

            'form.year_level' => ['nullable', Rule::in($this->yearLevels())],
            'form.section'    => ['nullable', Rule::in($this->sections())],

            'form.email'       => ['nullable', 'email', 'max:255'],
            'form.middle_name' => ['nullable', 'string', 'max:100'],
            'form.suffix'      => ['nullable', 'string', 'max:20'],
            'form.gender'      => ['nullable', 'string', 'max:20'],
            'form.nationality' => ['nullable', 'string', 'max:50'],
            'form.birthplace'  => ['nullable', 'string', 'max:150'],
            'form.address'     => ['nullable', 'string', 'max:255'],
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
        $this->resetValidation();
        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $record = StudentRecord::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $id;

        $form = $this->blankForm();
        foreach (array_keys($form) as $field) {
            $value = $record->{$field};

            // Date casts come back as Carbon; the date input wants Y-m-d.
            $form[$field] = $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : (string) ($value ?? '');
        }

        $this->form = $form;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        // Empty strings are not the same as "no value". Writing '' into a
        // nullable date column fails, and '' in a nullable text column makes
        // a template print a blank line where it should print nothing.
        $data = collect($this->form)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();

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

        $this->dispatch('record-saved', message: $message);
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
     * Those documents are in circulation and each one verifies against this
     * record's certificate rows. Removing the student would leave every QR
     * code already printed resolving to nothing, which a verifier reads as
     * forgery rather than as an administrative tidy-up. Where a student
     * should no longer appear, the status field is the right instrument.
     */
    public function confirmDelete(int $id): void
    {
        $issued = DB::table('certificates')
            ->where('student_record_id', $id)
            ->count();

        if ($issued > 0) {
            $this->dispatch('record-blocked', message:
                "This student has {$issued} issued document" .
                ($issued === 1 ? '' : 's') .
                '. Deleting the record would break verification for documents ' .
                'already released. Revoke the documents first, or change the ' .
                "student's status instead.");
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
        // issued between the confirmation prompt and the click.
        $issued = DB::table('certificates')
            ->where('student_record_id', $this->pendingDeleteId)
            ->count();

        if ($issued > 0) {
            $this->pendingDeleteId = null;
            $this->dispatch('record-blocked', message:
                'A document was issued for this student while the prompt was ' .
                'open. The record was not deleted.');
            return;
        }

        StudentRecord::whereKey($this->pendingDeleteId)->delete();

        $this->pendingDeleteId = null;
        unset($this->records);

        $this->dispatch('record-saved', message: 'Record deleted.');
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
    }

    // ------------------------------------------------------------------

    public function render()
    {
        return view('livewire.students.manage-records');
    }
}
