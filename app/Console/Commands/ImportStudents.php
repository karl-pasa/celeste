<?php

namespace App\Console\Commands;

use App\Models\StudentRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ImportStudents extends Command
{
    protected $signature = 'celeste:import-students
                            {file : Path to the student CSV}
                            {--grades= : Optional path to a grades CSV for transcripts}
                            {--update : Overwrite records that already exist}
                            {--dry-run : Validate and report without writing anything}';

    protected $description = 'Import student records (and optionally grades) from CSV';

    protected array $required = [
        'student_number', 'first_name', 'last_name', 'college', 'program', 'status', 'email',
    ];

    protected array $optional = [
        'middle_name', 'suffix', 'birth_date', 'address', 'major', 'year_level',
        'academic_year', 'semester', 'date_admitted', 'date_graduated',
        'latin_honor', 'general_weighted_average', 'year_level', 'email',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}. Check the path and try again.");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === null) {
            return self::FAILURE;
        }

        $this->info(count($rows) . ' rows read from ' . basename($path));

        [$valid, $errors] = $this->validateRows($rows);

        if ($errors !== []) {
            $this->newLine();
            $this->error(count($errors) . ' rows have problems and were not imported:');

            foreach (array_slice($errors, 0, 25) as $error) {
                $this->line("  Line {$error['line']}: {$error['message']}");
            }

            if (count($errors) > 25) {
                $this->line('  … and ' . (count($errors) - 25) . ' more.');
            }

            $this->newLine();
        }

        if ($valid === []) {
            $this->error('Nothing to import.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info(count($valid) . ' rows would import cleanly. Nothing was written.');
            $this->table(
                ['Student number', 'Name', 'Program', 'Status'],
                collect($valid)->take(10)->map(fn ($r) => [
                    $r['student_number'],
                    trim("{$r['first_name']} {$r['last_name']}"),
                    $r['program'],
                    $r['status'],
                ])->all()
            );

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($valid as $row) {
            $existing = StudentRecord::where('student_number', $row['student_number'])->first();

            if ($existing && ! $this->option('update')) {
                $skipped++;
                continue;
            }

            if ($existing) {
                $existing->update($row);
                $updated++;
            } else {
                StudentRecord::create($row);
                $created++;
            }
        }

        $this->newLine();
        $this->info("Created {$created}, updated {$updated}, skipped {$skipped}.");

        if ($skipped > 0) {
            $this->comment('Skipped rows already exist. Re-run with --update to overwrite them.');
        }

        if ($this->option('grades')) {
            $this->importGrades($this->option('grades'));
        }

        return self::SUCCESS;
    }

    protected function importGrades(string $path): int
    {
        if (! is_readable($path)) {
            $this->error("Cannot read the grades file at {$path}.");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === null) {
            return self::FAILURE;
        }

        $grouped = collect($rows)->groupBy('student_number');
        $applied = 0;
        $missing = [];

        foreach ($grouped as $studentNumber => $subjects) {
            $student = StudentRecord::where('student_number', $studentNumber)->first();

            if (! $student) {
                $missing[] = $studentNumber;
                continue;
            }

            $student->update([
                'grades' => $subjects->map(fn ($row) => [
                    'code'          => $row['code'] ?? '',
                    'title'         => $row['title'] ?? '',
                    'units'         => (float) ($row['units'] ?? 0),
                    'grade'         => $row['grade'] ?? '',
                    'remarks'       => $row['remarks'] ?? 'Passed',
                    'academic_year' => $row['academic_year'] ?? '',
                    'semester'      => $row['semester'] ?? '',
                ])->values()->all(),
            ]);

            $applied++;
        }

        $this->newLine();
        $this->info("Grades applied to {$applied} students.");

        if ($missing !== []) {
            $this->warn(count($missing) . ' student numbers in the grades file have no matching record: '
                . implode(', ', array_slice($missing, 0, 10))
                . (count($missing) > 10 ? ' …' : ''));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    protected function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->error("Could not open {$path}.");

            return null;
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            $this->error('The file appears to be empty.');
            fclose($handle);

            return null;
        }

        $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $rows = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            // Skip blank lines, which trailing newlines in Excel exports create.
            if ($data === [null] || (count($data) === 1 && trim((string) $data[0]) === '')) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = isset($data[$index]) ? trim((string) $data[$index]) : '';
            }

            $row['__line'] = $line;
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{line:int, message:string}>}
     */
    protected function validateRows(array $rows): array
    {
        $valid = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = $row['__line'];
            unset($row['__line']);

            $validator = Validator::make($row, [
                'student_number' => ['required', 'string', 'max:50'],
                'first_name'     => ['required', 'string', 'max:100'],
                'last_name'      => ['required', 'string', 'max:100'],
                'college'        => ['required', 'string', 'max:150'],
                'program'        => ['required', 'string', 'max:150'],
                'status'         => ['required', 'in:enrolled,graduated,transferred,inactive'],
                'general_weighted_average' => ['nullable', 'numeric', 'between:1,5'],
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'line'    => $line,
                    'message' => implode(' ', $validator->errors()->all()),
                ];

                continue;
            }

            // A duplicate student number inside one file almost always means a
            // copy-paste mistake in the spreadsheet, so flag it rather than
            // letting the last row silently win.
            if (isset($seen[$row['student_number']])) {
                $errors[] = [
                    'line'    => $line,
                    'message' => "Duplicate student number {$row['student_number']} (first seen on line {$seen[$row['student_number']]}).",
                ];

                continue;
            }

            $seen[$row['student_number']] = $line;
            $valid[] = $this->cast($row);
        }

        return [$valid, $errors];
    }

    protected function cast(array $row): array
    {
        $clean = [];

        foreach (array_merge($this->required, $this->optional) as $column) {
            if (! array_key_exists($column, $row) || $row[$column] === '') {
                continue;
            }

            $clean[$column] = $row[$column];
        }

        foreach (['birth_date', 'date_admitted', 'date_graduated'] as $dateColumn) {
            if (! empty($clean[$dateColumn])) {
                try {
                    $clean[$dateColumn] = Carbon::parse($clean[$dateColumn])->toDateString();
                } catch (\Throwable) {
                    unset($clean[$dateColumn]);
                }
            }
        }

        if (isset($clean['general_weighted_average'])) {
            $clean['general_weighted_average'] = (float) $clean['general_weighted_average'];
        }

        $clean['status'] = strtolower($clean['status']);

        return $clean;
    }
}
