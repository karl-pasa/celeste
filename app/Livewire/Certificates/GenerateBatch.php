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
    public string $yearLevel = '';
    public string $section = '';
    public string $search = '';

    /**
     * Once the registrar types a name of their own, the filters stop
     * overwriting it. Clearing the box hands control back to the suggestion.
     */
    public bool $labelEdited = false;

    /** @var array<int> */
    public array $selected = [];
    public bool $selectPage = false;

    public $csv;
    public ?int $batchId = null;

    /* -----------------------------------------------------------------
     |  Filter hooks
     | ----------------------------------------------------------------- */

    public function updatedDocumentType(): void
    {
        // A diploma is only issued at the final year. If another year was
        // already chosen, drop it rather than keep filtering by a value the
        // dropdown no longer offers.
        if ($this->isDiploma() && $this->yearLevel !== '' && $this->yearLevel !== $this->finalYear()) {
            $this->yearLevel = '';
            $this->section = '';
        }

        $this->afterFilterChange();
    }

    public function updatedCollege(): void
    {
        // A program from the previous college would keep filtering the list
        // after the college changed, showing nothing and looking broken.
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

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectPage = false;
    }

    public function updatedLabel(): void
    {
        $this->labelEdited = trim($this->label) !== '';
    }

    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->students->pluck('id')->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    protected function afterFilterChange(): void
    {
        $this->resetPage();
        $this->selectPage = false;
        $this->selected = [];
        $this->suggestLabel();
    }

    protected function suggestLabel(): void
    {
        if ($this->labelEdited) {
            return;
        }

        $this->label = trim($this->program . ' ' . $this->cohortCode());
    }

    /** "3rd Year" + "A" reads as "3A". */
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

    protected function isDiploma(): bool
    {
        return $this->documentType === Certificate::TYPE_DIPLOMA;
    }

    protected function finalYear(): string
    {
        return (string) collect(config('celeste.academics.year_levels', []))->last();
    }

    /* -----------------------------------------------------------------
     |  CSV
     | ----------------------------------------------------------------- */

    /**
     * Upload a CSV of student numbers to pre-select a cohort.
     * One column, header optional. Numbers are matched digits-only, the same
     * way they are stored.
     */
    public function importCsv(): void
    {
        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $rows = array_filter(array_map('trim', file($this->csv->getRealPath())));
        $numbers = collect($rows)
            ->map(fn ($row) => trim(explode(',', $row)[0], " \t\n\r\0\x0B\"'\xEF\xBB\xBF"))
            ->reject(fn ($n) => $n === '' || strtolower($n) === 'student_number')
            ->map(fn ($n) => preg_replace('/\D+/', '', $n))
            ->filter()
            ->unique()
            ->values()
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

    /* -----------------------------------------------------------------
     |  List and generation
     | ----------------------------------------------------------------- */

    public function getStudentsProperty()
    {
        return StudentRecord::query()
            ->when($this->college, fn ($q) => $q->where('college', $this->college))
            ->when($this->program, fn ($q) => $q->where('program', $this->program))
            // A diploma batch only ever lists final-year students, even when
            // the year level filter is left on "all".
            ->when(
                $this->isDiploma(),
                fn ($q) => $q->where('year_level', $this->finalYear()),
                fn ($q) => $q->when($this->yearLevel !== '', fn ($y) => $y->where('year_level', $this->yearLevel))
            )
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

    /* -----------------------------------------------------------------
     |  Dropdown options
     | ----------------------------------------------------------------- */

    /**
     * The official college list, and only that, from config/celeste.php so a
     * stray college name left in student_records cannot appear in the filter.
     * Those students are still reachable through the search box.
     */
    protected function collegeOptions()
    {
        return collect(config('celeste.colleges', []))
            ->filter()
            ->values();
    }

    /**
     * Programs for the chosen college. Config first; a college with no config
     * entry falls back to the programs present in its records. With no college
     * chosen, every configured program is offered.
     */
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