<?php

namespace App\Livewire\Certificates;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class GenerateBatch extends Component
{
    use WithPagination, WithFileUploads;

    public string $documentType = Certificate::TYPE_DIPLOMA;
    public string $label = '';
    public string $college = '';
    public string $program = '';
    public string $status = '';
    public string $search = '';

    /** @var array<int> */
    public array $selected = [];
    public bool $selectPage = false;

    public $csv;
    public ?int $batchId = null;

    public function mount(): void
    {
        $this->label = 'Batch run ' . now()->format('M j, Y');
        $this->status = 'graduated';
    }

    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->students->pluck('id')->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function updating($field): void
    {
        if (in_array($field, ['college', 'program', 'status', 'search'], true)) {
            $this->resetPage();
            $this->selectPage = false;
        }
    }

    /**
     * Upload a CSV of student numbers to pre-select a cohort.
     * One column, header optional.
     */
    public function importCsv(): void
    {
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $rows = array_filter(array_map('trim', file($this->csv->getRealPath())));
        $numbers = collect($rows)
            ->map(fn ($row) => trim(explode(',', $row)[0], " \t\n\r\0\x0B\"'"))
            ->reject(fn ($n) => $n === '' || strtolower($n) === 'student_number')
            ->all();

        $matched = StudentRecord::whereIn('student_number', $numbers)->pluck('id')->all();
        $missing = count($numbers) - count($matched);

        $this->selected = array_values(array_unique(array_merge($this->selected, $matched)));
        $this->csv = null;

        session()->flash(
            'batch-import',
            $missing > 0
                ? count($matched) . ' records matched. ' . $missing . ' student numbers were not found.'
                : count($matched) . ' records matched and selected.'
        );
    }

    public function getStudentsProperty()
    {
        return StudentRecord::query()
            ->when($this->college, fn ($q) => $q->where('college', $this->college))
            ->when($this->program, fn ($q) => $q->where('program', $this->program))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->where(function ($s) {
                    $s->where('last_name', 'ilike', "%{$this->search}%")
                      ->orWhere('student_number', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(10);
    }

    public function generate(CertificateGenerator $generator): void
    {
        $this->validate([
            'selected'     => ['required', 'array', 'min:1'],
            'label'        => ['required', 'string', 'max:120'],
            'documentType' => ['required', 'in:' . implode(',', array_keys(Certificate::types()))],
        ], ['selected.required' => 'Select at least one student record.']);

        $batch = $generator->issueBatch(
            $this->selected,
            $this->documentType,
            auth()->user(),
            $this->label,
        );

        $this->batchId = $batch->id;
        $this->selected = [];
        $this->selectPage = false;
    }

    public function getBatchProperty(): ?CertificateBatch
    {
        return $this->batchId ? CertificateBatch::find($this->batchId) : null;
    }

    public function render()
    {
        return view('livewire.certificates.generate-batch', [
            'types'    => Certificate::types(),
            'colleges' => StudentRecord::distinct()->orderBy('college')->pluck('college'),
            'programs' => StudentRecord::when($this->college, fn ($q) => $q->where('college', $this->college))
                ->distinct()->orderBy('program')->pluck('program'),
        ]);
    }
}
