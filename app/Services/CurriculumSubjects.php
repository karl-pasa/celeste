<?php

namespace App\Services;

use App\Models\CurriculumSubject;
use App\Models\StudentRecord;
use RuntimeException;

/**
 * Finds the subjects a student takes in a given term from the program of study
 * and shapes them as the `subjects` rows of the Certificate of Enrolment payload.
 *
 *   $payload['subjects'] = CurriculumSubjects::forStudent($student);
 *
 * Program, year level and semester default to the student's record.
 */
class CurriculumSubjects
{
    /** "Bachelor of Science in Information Technology" -> BSIT; config('celeste.programs') overrides. */
    public static function programCode(string $program): string
    {
        $map = config('celeste.programs', []);
        if (isset($map[$program])) return $map[$program];
        if (preg_match('/^[A-Z]{2,8}$/', $program)) return $program;
        $stop = ['in', 'of', 'and', 'the', 'major'];
        $out = '';
        foreach (preg_split('/[\s,]+/', $program) as $w) {
            if (strtolower($w) === 'major') break;
            if ($w !== '' && ! in_array(strtolower($w), $stop, true)) $out .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        return $out;
    }

    public static function year(string|int $y): int      // "3rd Year", "Third Year", 3
    {
        if (is_numeric($y)) return (int) $y;
        $y = strtolower($y);
        foreach (['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4] as $w => $n)
            if (str_contains($y, $w)) return $n;
        if (preg_match('/\d/', $y, $m)) return (int) $m[0];
        throw new RuntimeException("Unrecognised year level: {$y}");
    }

    public static function term(string $s): string        // "1st Semester", "First Semester", "Summer", "Midyear"
    {
        $s = strtolower($s);
        if (str_contains($s, 'summer') || str_contains($s, 'midyear') || str_contains($s, 'mid-year')) return 'Summer';
        if (str_contains($s, '1st') || str_contains($s, 'first')) return '1st';
        if (str_contains($s, '2nd') || str_contains($s, 'second')) return '2nd';
        throw new RuntimeException("Unrecognised semester: {$s}");
    }

    public static function forStudent(StudentRecord $s, ?string $program = null, $year = null, ?string $semester = null): array
    {
        $code = self::programCode($program ?? $s->program);
        $y = self::year($year ?? $s->year_level);
        $t = self::term($semester ?? $s->semester);

        $rows = CurriculumSubject::where(['program_code' => $code, 'year_level' => $y, 'term' => $t])
            ->orderBy('sort')->get()->map->only(['code', 'title', 'unit', 'lab'])->all();

        if (! $rows) {
            throw new RuntimeException("No program of study loaded for {$code}, year {$y}, {$t} term.");
        }
        return self::shape($rows);
    }

    /** Pure: curriculum rows in, payload subject rows out. */
    public static function shape(array $rows): array
    {
        return array_map(fn ($r) => [
            'code'         => '',                                   // per-term class code: not in the program of study
            'subject_code' => $r['code'],
            'description'  => $r['title'],
            'units'        => number_format((float) $r['unit'], 1, '.', ''),
            'tf'           => number_format((float) $r['unit'], 1, '.', ''),
            'lab'          => number_format(((float) $r['lab']) / 3, 1, '.', ''),  // lab hours / 3 = lab units
            'schedule'     => [],
            'instructor'   => '',
            'section'      => '',
        ], $rows);
    }
}
