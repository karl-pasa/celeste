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
     * Four characters, which is the shortest useful prefix of a student
     * number given the year-first format. Fewer than that matches a whole
     * intake and is not a search so much as a listing.
     */
    protected const MIN_SEARCH_LENGTH = 4;

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
        'studentId.required'    => 'Enter a student number first.',
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
     * Cached for the request. The eligibility check, the summary panel, and
     * the generate action all read it, which previously meant three identical
     * queries for one page render.
     */
    #[Computed]
    public function student(): ?StudentRecord
    {
        return $this->studentId ? StudentRecord::find($this->studentId) : null;
    }

    /**
     * Matching student records, searched by student number only.
     *
     * A prefix match is used rather than a contains match, and the difference
     * is the whole reason this is fast. student_number already carries a
     * unique B-tree index, and a B-tree can seek directly to a known prefix.
     * A leading wildcard cannot use that index at all and would scan every
     * row instead.
     *
     * This assumes the registrar types a student number from its beginning,
     * which the year-first format makes natural. Someone typing only the tail
     * digits will not match; the guidance in the interface should therefore
     * ask for the number as printed.
     */
    #[Computed]
    public function results()
    {
        $term = $this->normalisedTerm();

        if (mb_strlen($term) < self::MIN_SEARCH_LENGTH) {
            return collect();
        }

        return StudentRecord::query()
            ->select(['id', 'student_number', 'first_name', 'middle_name', 'last_name', 'suffix', 'program', 'status'])
            ->where('student_number', 'ilike', $term . '%')
            ->orderBy('student_number')
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    /**
     * Tidy what was typed before it reaches the query.
     *
     * Values pasted from a spreadsheet or a printed list frequently carry
     * surrounding whitespace, and a number typed with spaces around the
     * hyphen would otherwise fail to match a record that is stored without
     * them.
     */
    protected function normalisedTerm(): string
    {
        return preg_replace('/\s+/', '', trim($this->search)) ?? '';
    }

    /**
     * Whether the term is too short to search on, so the interface can say so
     * rather than showing an empty list that reads as a failed search.
     */
    #[Computed]
    public function searchTooShort(): bool
    {
        $length = mb_strlen($this->normalisedTerm());

        return $length > 0 && $length < self::MIN_SEARCH_LENGTH;
    }

    /**
     * Whether a search ran and found nothing, which is a different state from
     * not having searched yet and should read differently to the registrar.
     */
    #[Computed]
    public function searchFoundNothing(): bool
    {
        return mb_strlen($this->normalisedTerm()) >= self::MIN_SEARCH_LENGTH
            && $this->results->isEmpty();
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

        // The results list selects a subset of columns, and issuance needs the
        // whole record, so the full model is loaded here rather than reusing
        // whatever the search returned.
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
