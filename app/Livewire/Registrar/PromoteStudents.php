<?php

namespace App\Livewire\Registrar;

use App\Models\AuditLog;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Moves a whole group of students up one year level, with a preview first.
 *
 * Only students whose status is Enrolled are touched. Students in the
 * graduating year are marked Graduated instead of being moved. Sections are
 * kept as they are.
 */
class PromoteStudents extends Component
{
    public string $college = '';
    public string $program = '';
    public string $finalLevel = '';
    public string $academicYear = '';
    public string $semester = '';

    public ?string $notice = null;
    public string $noticeType = 'success';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isRegistrar(), 403);
    }

    public function mount(): void
    {
        $levels = $this->levels();
        $this->finalLevel = in_array('4th Year', $levels, true) ? '4th Year' : (string) (end($levels) ?: '');
    }

    public function updated(string $name): void
    {
        if ($name === 'college') {
            $this->program = '';
        }

        $this->notice = null;
    }

    /** @return array<int, string> year levels in order, from the configuration */
    private function levels(): array
    {
        return array_values((array) config('celeste.academics.year_levels', ['1st Year', '2nd Year', '3rd Year', '4th Year']));
    }

    private function scope()
    {
        return StudentRecord::query()
            ->where('college', $this->college)
            ->when($this->program !== '', fn ($q) => $q->where('program', $this->program));
    }

    /** What the button would do, for the preview table. */
    private function plan(): array
    {
        $levels = $this->levels();
        $final  = array_search($this->finalLevel, $levels, true);

        if ($this->college === '' || $final === false) {
            return ['rows' => [], 'total' => 0, 'other' => 0, 'notEnrolled' => 0];
        }

        $counts = $this->scope()->where('status', 'enrolled')
            ->selectRaw('year_level, count(*) as total')
            ->groupBy('year_level')
            ->pluck('total', 'year_level')
            ->all();

        $rows  = [];
        $total = 0;

        foreach (array_slice($levels, 0, $final + 1, true) as $i => $level) {
            $n = (int) ($counts[$level] ?? 0);
            $rows[] = [
                'from'  => $level,
                'to'    => $i === $final ? 'Graduated' : $levels[$i + 1],
                'count' => $n,
            ];
            $total += $n;
        }

        $enrolled    = $this->scope()->where('status', 'enrolled')->count();
        $notEnrolled = $this->scope()->where('status', '!=', 'enrolled')->count();

        return [
            'rows'        => $rows,
            'total'       => $total,
            'other'       => max(0, $enrolled - $total),   // enrolled, but no year level or above the graduating year
            'notEnrolled' => $notEnrolled,
        ];
    }

    public function promote(): void
    {
        $levels = $this->levels();

        $this->validate([
            'college'      => ['required', 'string'],
            'finalLevel'   => ['required', 'in:' . implode(',', $levels)],
            'academicYear' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'semester'     => ['nullable', 'in:1st Semester,2nd Semester,Summer'],
        ], [
            'college.required'      => 'Choose a college first.',
            'academicYear.regex'    => 'Write the academic year like 2027-2028.',
        ]);

        $final = array_search($this->finalLevel, $levels, true);

        $extra = array_filter([
            'academic_year' => $this->academicYear !== '' ? $this->academicYear : null,
            'semester'      => $this->semester !== '' ? $this->semester : null,
        ]);

        $moved = 0;
        $graduated = 0;

        DB::transaction(function () use ($levels, $final, $extra, &$moved, &$graduated) {
            // From the top down, so nobody is moved twice.
            for ($i = $final; $i >= 0; $i--) {
                $query = $this->scope()->where('status', 'enrolled')->where('year_level', $levels[$i]);

                if ($i === $final) {
                    $graduated += $query->update(['status' => 'graduated']);
                } else {
                    $moved += $query->update(['year_level' => $levels[$i + 1]] + $extra);
                }
            }
        });

        AuditLog::record('students.year_level_moved', auth()->user(), [
            'college' => $this->college,
            'program' => $this->program !== '' ? $this->program : 'all',
            'moved'   => $moved,
            'graduated' => $graduated,
        ]);

        $this->notice = "{$moved} " . ($moved === 1 ? 'student' : 'students') . ' moved up'
            . ($graduated ? ", {$graduated} marked as graduated" : '') . '.';
        $this->noticeType = ($moved + $graduated) > 0 ? 'success' : 'warning';
    }

    public function render()
    {
        return view('livewire.registrar.promote-students', [
            'colleges' => StudentRecord::query()->distinct()->orderBy('college')->pluck('college')->filter()->values(),
            'programs' => $this->college === ''
                ? collect()
                : StudentRecord::query()->where('college', $this->college)->distinct()->orderBy('program')->pluck('program')->filter()->values(),
            'levels'   => $this->levels(),
            'plan'     => $this->plan(),
        ]);
    }
}