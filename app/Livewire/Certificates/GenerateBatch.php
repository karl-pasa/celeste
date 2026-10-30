<?php

namespace App\Livewire\Certificates;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class GenerateBatch extends Component
{
    use WithPagination;

    public string $documentType = Certificate::TYPE_DIPLOMA;
    public string $label = '';
    public string $college = '';
    public string $program = '';
    public string $yearLevel = '';
    public string $section = '';
    public string $search = '';

    /**
     * Student ids as STRINGS. A checkbox's value in the browser is always a
     * string, and Livewire only draws the tick when the value is found in
     * this array with the same type -- integers here left the box empty
     * even though the "N selected" count went up.
     *
     * @var array<int, string>
     */
    public array $selected = [];
    public bool $selectPage = false;

    public ?int $batchId = null;

    /**
     * Once the registrar types a name of their own, the filters stop
     * overwriting it. Clearing the box hands control back to the suggestion.
     */
    public bool $labelEdited = false;

    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->students->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectPage = false;
    }

    public function updatedLabel(): void
    {
        $this->labelEdited = trim($this->label) !== '';
    }

    public function updatedCollege(): void
    {
        $this->program = '';
        $this->yearLevel = '';
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedProgram(): void
    {
        $this->yearLevel = '';
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedYearLevel(): void
    {
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedSection(): void
    {
        $this->afterFilterChange();
    }

    protected function afterFilterChange(): void
    {
        $this->resetPage();
        $this->selectPage = false;
        $this->selected = [];
    }

    public function getStudentsProperty()
    {
        return StudentRecord::query()
            ->when($this->college, fn ($q) => $q->where('college', $this->college))
            ->when($this->program, fn ($q) => $q->where('program', $this->program))
            ->when($this->yearLevel !== '', fn ($q) => $q->where('year_level', $this->yearLevel))
            ->when($this->section !== '', fn ($q) => $q->where('section', $this->section))
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
        $this->label = trim($this->label);

        // The registrar must name the batch themselves. Nothing is generated
        // until a name has been typed.

        try {
            $this->validate([
                'selected'     => ['required', 'array', 'min:1'],
                'label'        => ['required', 'string', 'min:3', 'max:120'],
                'documentType' => ['required', 'in:' . implode(',', array_keys(Certificate::types()))],
            ], [
                'selected.required' => 'Select at least one student record.',
                'label.required'    => 'Give the batch a name before generating.',
                'label.min'         => 'The batch name must be at least 3 characters.',
            ]);
        } catch (ValidationException $e) {
            // Tell the browser to bring the first error into view. The page
            // is long, and the field at fault is often off-screen.
            $this->dispatch('scroll-to-error');

            throw $e;
        }

        $batch = $generator->issueBatch(
            array_map('intval', $this->selected),
            $this->documentType,
            auth()->user(),
            $this->label,
        );

        $this->batchId = $batch->id;
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage('history');
    }

    public function getBatchProperty(): ?CertificateBatch
    {
        return $this->batchId ? CertificateBatch::find($this->batchId) : null;
    }

    public function getHistoryProperty()
    {
        return CertificateBatch::query()->latest('id')->paginate(8, ['*'], 'history');
    }

    protected function collegeOptions()
    {
        return collect(config('celeste.colleges', []))->filter()->values();
    }

    protected function programOptions()
    {
        $map = collect(config('celeste.programs', []));

        if ($this->college === '') {
            return $map->flatten()->unique()->sort()->values();
        }

        $configured = collect($map->get($this->college, []));

        if ($configured->isNotEmpty()) {
            return $configured->values();
        }

        return StudentRecord::query()
            ->where('college', $this->college)
            ->distinct()
            ->orderBy('program')
            ->pluck('program');
    }

    protected function cohortCode(): string
    {
        if ($this->yearLevel === '' && $this->section === '') {
            return '';
        }

        if (preg_match('/\d+/', $this->yearLevel, $match)) {
            return $match[0] . $this->section;
        }

        return trim($this->yearLevel . ' ' . $this->section);
    }

    public function render()
    {
        return view('livewire.certificates.generate-batch', [
            'types'      => Certificate::types(),
            'colleges'   => $this->collegeOptions(),
            'programs'   => $this->programOptions(),
            'yearLevels' => config('celeste.academics.year_levels', []),
            'sections'   => config('celeste.academics.sections', []),
        ]);
    }
}