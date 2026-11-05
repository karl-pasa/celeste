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

    /**
     * A University Diploma goes only to graduating students, so choosing it
     * locks the list to 4th year. Other year levels are greyed out.
     */
    public const DIPLOMA_YEAR_NUMBER = 4;

    public function getDiplomaOnlyProperty(): bool
    {
        return $this->documentType === Certificate::TYPE_DIPLOMA;
    }

    /** The configured year level that means "4th year", e.g. "4th Year". */
    protected function finalYearLevel(): string
    {
        foreach (config('celeste.academics.year_levels', []) as $level) {
            if (preg_match('/\d+/', (string) $level, $m) && (int) $m[0] === self::DIPLOMA_YEAR_NUMBER) {
                return (string) $level;
            }
        }

        return '4th Year';
    }

    /** Year level a filter reset should fall back to: 4th year for a diploma, otherwise none. */
    protected function lockedYear(): string
    {
        return $this->diplomaOnly ? $this->finalYearLevel() : '';
    }

    public function mount(): void
    {
        // The page opens on University Diploma, so it starts locked to 4th year.
        $this->yearLevel = $this->lockedYear();
    }

    public function updatedDocumentType(): void
    {
        // Switching to a diploma sets the year level to 4th year; switching
        // away from it lifts the lock. Either way the old ticks are dropped,
        // because they may belong to students who no longer qualify.
        $this->yearLevel = $this->lockedYear();
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->students->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    /**
     * Keep the header box honest: ticked only while every student on this
     * page is selected, so unticking one row clears it.
     */
    public function updatedSelected(): void
    {
        $ids = $this->students->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->selectPage = $ids !== [] && array_diff($ids, array_map('strval', $this->selected)) === [];
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
        $this->yearLevel = $this->lockedYear();
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedProgram(): void
    {
        $this->yearLevel = $this->lockedYear();
        $this->section = '';
        $this->afterFilterChange();
    }

    public function updatedYearLevel(): void
    {
        if ($this->diplomaOnly) {
            $this->yearLevel = $this->finalYearLevel();
        }

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
            ->when($this->diplomaOnly, fn ($q) => $q->where('year_level', $this->finalYearLevel()))
            ->when(! $this->diplomaOnly && $this->yearLevel !== '', fn ($q) => $q->where('year_level', $this->yearLevel))
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

        // Never trust the ticks alone: a diploma batch may only contain 4th
        // year students, even if the page was left open from another filter.
        if ($this->diplomaOnly) {
            $eligible = StudentRecord::query()
                ->whereIn('id', array_map('intval', $this->selected))
                ->where('year_level', $this->finalYearLevel())
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            if (count($eligible) !== count($this->selected)) {
                $this->selected = array_values(array_intersect(array_map('strval', $this->selected), $eligible));
                $this->addError('selected', 'A University Diploma can only be generated for 4th year students. Students who are not in 4th year were unticked. Review the list and generate again.');
                $this->dispatch('scroll-to-error');

                return;
            }
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
            'finalYear'  => $this->finalYearLevel(),
            'sections'   => config('celeste.academics.sections', []),
        ]);
    }
}