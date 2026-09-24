<?php

namespace App\Livewire\Certificates;

use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GenerateSingle extends Component
{
    public string $search = '';
    public ?int $studentId = null;
    // Nothing is preselected. Issuing the wrong document type is a real
    // clerical error, and a default choice is one the registrar never
    // consciously made.
    public ?string $documentType = null;
    public string $issuedOn = '';
    public string $purpose = '';
    public ?int $issuedId = null;

    /**
     * Three characters rather than two.
     *
     * The trigram index that makes this search fast works on three-character
     * sequences, so a two-character term cannot use it and falls back to
     * scanning the table. Two characters also matches most of the roll, which
     * is not a useful result for the registrar in any case.
     */
    protected const MIN_SEARCH_LENGTH = 3;

    /** Enough to choose from without the list becoming something to read. */
    protected const MAX_RESULTS = 8;

    public function mount(): void
    {
        $this->issuedOn = now()->toDateString();
    }

    public function rules(): array
    {
        return [
            'studentId'    => ['required', 'exists:student_records,id'],
            'documentType' => ['required', 'in:' . implode(',', array_keys(Certificate::types()))],
            'issuedOn'     => ['required', 'date'],
            'purpose'      => ['nullable', 'string', 'max:160'],
        ];
    }

    protected array $messages = [
        'studentId.required'    => 'Pick a student record first.',
        'documentType.required' => 'Choose which document to issue.',
        'documentType.in'       => 'Choose which document to issue.',
    ];

    /**
     * Click to choose, click again to undo. Selecting a different type
     * replaces the current one.
     */
    public function selectType(string $type): void
    {
        if (! array_key_exists($type, Certificate::types())) {
            return;
        }

        $this->documentType = $this->documentType === $type ? null : $type;

        // A type change invalidates the previous result panel.
        $this->issuedId = null;

        $this->resetValidation('documentType');
    }

    public function clearType(): void
    {
        $this->documentType = null;
        $this->issuedId = null;
        $this->resetValidation('documentType');
    }

    public function isSelected(string $type): bool
    {
        return $this->documentType === $type;
    }

    #[Computed]
    public function ready(): bool
    {
        return $this->studentId !== null && $this->documentType !== null;
    }

    public function selectStudent(int $id): void
    {
        $this->studentId = $id;
        $this->search = '';
        $this->issuedId = null;
    }

    public function clearStudent(): void
    {
        $this->studentId = null;
    }

    /**
     * The chosen student.
     *
     * Marked as a computed property so the lookup runs once per request
     * rather than on every access. The eligibility check, the summary panel,
     * and generate() all read it, which previously meant three identical
     * queries for one page render.
     */
    #[Computed]
    public function student(): ?StudentRecord
    {
        return $this->studentId ? StudentRecord::find($this->studentId) : null;
    }

    /**
     * Matching student records.
     *
     * Two things keep this cheap. Only the columns the result list displays
     * are selected, rather than every column of a table that carries more
     * than thirty, most of which relate to transcript printing. And the term
     * is bound once as a parameter instead of being interpolated three times,
     * so the database can reuse its plan.
     */
    #[Computed]
    public function results()
    {
        $term = trim($this->search);

        if (mb_strlen($term) < self::MIN_SEARCH_LENGTH) {
            return collect();
        }

        $like = '%' . $term . '%';

        return StudentRecord::query()
            ->select(['id', 'student_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'program', 'status'])
            ->where(function ($q) use ($like) {
                $q->where('last_name', 'ilike', $like)
                  ->orWhere('first_name', 'ilike', $like)
                  ->orWhere('student_number', 'ilike', $like);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    /**
     * Whether the term is long enough to search on, so the interface can say
     * "keep typing" rather than showing an empty result list that looks like
     * a failed search.
     */
    #[Computed]
    public function searchTooShort(): bool
    {
        $length = mb_strlen(trim($this->search));

        return $length > 0 && $length < self::MIN_SEARCH_LENGTH;
    }

    /**
     * Warn before issuing a document the record does not support —
     * a diploma for someone still enrolled, for instance.
     */
    #[Computed]
    public function eligibility(): ?string
    {
        $student = $this->student;

        if (! $student || $this->documentType === null) {
            return null;
        }

        return match (true) {
            $this->documentType === Certificate::TYPE_DIPLOMA && $student->status !== 'graduated'
                => 'This student is not marked as graduated. Update the record before issuing a diploma.',
            $this->documentType === Certificate::TYPE_ENROLMENT && $student->status !== 'enrolled'
                => 'This student is not currently enrolled, so a Certificate of Enrolment may not be appropriate.',
            $this->documentType === Certificate::TYPE_TOR && empty($student->grades)
                => 'No grade rows are on file, so the transcript would print empty.',
            default => null,
        };
    }

    public function generate(CertificateGenerator $generator): void
    {
        $this->validate();

        // The results list selects a subset of columns, and generate() needs
        // the whole record, so the full model is loaded here rather than
        // reusing whatever the search returned.
        $student = StudentRecord::findOrFail($this->studentId);

        $certificate = $generator->issue(
            $student,
            $this->documentType,
            auth()->user(),
            [
                'issued_on' => $this->issuedOn,
                'payload'   => $this->purpose ? ['purpose' => $this->purpose] : [],
            ],
        );

        $this->issuedId = $certificate->id;
        $this->purpose = '';

        // The chosen student may now hold a document it did not a moment ago,
        // so any cached view of it is stale.
        unset($this->student);

        $this->dispatch('certificate-issued', serial: $certificate->serial_number);
    }

    #[Computed]
    public function issued(): ?Certificate
    {
        return $this->issuedId ? Certificate::find($this->issuedId) : null;
    }

    public function render()
    {
        return view('livewire.certificates.generate-single', [
            'types' => Certificate::types(),
        ]);
    }
}
