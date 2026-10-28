<?php

namespace App\Livewire\Certificates;

use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\StudentRecord;
use App\Services\CertificateGenerator;
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

    public ?int $batchId = null;

    /**
     * Once the registrar types a name of their own, the filters stop
     * overwriting it. Clearing the box hands control back to the suggestion.
     */
    public bool $labelEdited = false;

    public function updatedLabel(): void
    {
        $this->labelEdited = trim($this->label) !== '';
    }

    public function updatedCollege(): void
    {
        $this->program = '';
        $this->yearLevel = '';
        $this->section = '';
        $this->suggestLabel();
    }

    public function updatedProgram(): void
    {
        $this->yearLevel = '';
        $this->section = '';
        $this->suggestLabel();
    }

    public function updatedYearLevel(): void
    {
        $this->section = '';
        $this->suggestLabel();
    }

    public function updatedSection(): void
    {
        $this->suggestLabel();
    }

    /** Every student the current filters match. */
    protected function studentQuery()
    {
        return StudentRecord::query()
            ->when($this->college, fn ($q) => $q->where('college', $this->college))
            ->when($this->program, fn ($q) => $q->where('program', $this->program))
            ->when($this->yearLevel !== '', fn ($q) => $q->where('year_level', $this->yearLevel))
            ->when($this->section !== '', fn ($q) => $q->where('section', $this->section));
    }

    public function getMatchCountProperty(): int
    {
        // Nothing is counted until a college is chosen, so the whole student
        // body is never issued to by accident.
        return $this->college === '' ? 0 : $this->studentQuery()->count();
    }

    public function generate(CertificateGenerator $generator): void
    {
        $this->label = trim($this->label);

        $this->validate([
            'label'        => ['required', 'string', 'max:120'],
            'college'      => ['required', 'string'],
            'documentType' => ['required', 'in:' . implode(',', array_keys(Certificate::types()))],
        ], [
            'label.required'   => 'Give the batch a name before generating.',
            'college.required' => 'Choose at least a college so the batch has students.',
        ]);

        $ids = $this->studentQuery()->pluck('id')->all();

        if ($ids === []) {
            $this->addError('college', 'No student records match these filters.');
            return;
        }

        $batch = $generator->issueBatch($ids, $this->documentType, auth()->user(), $this->label);

        $this->batchId = $batch->id;
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

    protected function suggestLabel(): void
    {
        if ($this->labelEdited) {
            return;
        }

        $this->label = trim($this->program . ' ' . $this->cohortCode());
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