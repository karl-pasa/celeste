<?php

namespace App\Livewire\Certificates;

use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
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

    public function getReadyProperty(): bool
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

    public function getStudentProperty(): ?StudentRecord
    {
        return $this->studentId ? StudentRecord::find($this->studentId) : null;
    }

    public function getResultsProperty()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return StudentRecord::query()
            ->where(function ($q) {
                $q->where('last_name', 'ilike', "%{$this->search}%")
                  ->orWhere('first_name', 'ilike', "%{$this->search}%")
                  ->orWhere('student_number', 'ilike', "%{$this->search}%");
            })
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    /**
     * Warn before issuing a document the record does not support —
     * a diploma for someone still enrolled, for instance.
     */
    public function getEligibilityProperty(): ?string
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

        $certificate = $generator->issue(
            $this->student,
            $this->documentType,
            auth()->user(),
            [
                'issued_on' => $this->issuedOn,
                'payload'   => $this->purpose ? ['purpose' => $this->purpose] : [],
            ],
        );

        $this->issuedId = $certificate->id;
        $this->purpose = '';

        $this->dispatch('certificate-issued', serial: $certificate->serial_number);
    }

    public function getIssuedProperty(): ?Certificate
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