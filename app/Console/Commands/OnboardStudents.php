<?php

namespace App\Console\Commands;

use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Adds a batch of students in one step: import the records, then create the
 * sign-in accounts.
 *
 * The two operations were already available separately. Running them by hand
 * in the right order, with the right flags, is the sort of thing that goes
 * wrong at the end of a long day — records imported but accounts forgotten,
 * so a cohort exists in the system and none of them can sign in.
 */
class OnboardStudents extends Command
{
    protected $signature = 'celeste:onboard
                            {file : Path to the student CSV}
                            {--grades= : Optional grades CSV for transcripts}
                            {--dry-run : Validate and report, writing nothing}
                            {--update : Overwrite student records that already exist}
                            {--reset-passwords : Reset existing accounts back to the student number}';

    protected $description = 'Import a batch of students and create their sign-in accounts';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("Cannot read {$file}");
            $this->line('Check the path. From the project root it is usually:');
            $this->line('  storage\imports\students.csv');

            return self::FAILURE;
        }

        $before = [
            'records'  => StudentRecord::count(),
            'accounts' => User::where('role', User::ROLE_STUDENT)->count(),
        ];

        $this->line('');
        $this->line('  <options=bold>Step 1 of 2 — importing student records</>');
        $this->line('  ' . str_repeat('─', 60));

        $importArgs = ['file' => $file];
        if ($this->option('grades'))  { $importArgs['--grades'] = $this->option('grades'); }
        if ($this->option('dry-run')) { $importArgs['--dry-run'] = true; }
        if ($this->option('update'))  { $importArgs['--update'] = true; }

        if (Artisan::call('celeste:import-students', $importArgs, $this->output) !== self::SUCCESS) {
            $this->newLine();
            $this->error('  Import failed. No accounts were created.');
            $this->line('  Fix the rows reported above and run the same command again.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('  <options=bold>Step 2 of 2 — accounts that would be created</>');
            $this->line('  ' . str_repeat('─', 60));
            Artisan::call('celeste:create-student-accounts', ['--dry-run' => true], $this->output);

            $this->newLine();
            $this->info('  Dry run complete. Nothing was written.');
            $this->line('  Run again without --dry-run to apply.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  <options=bold>Step 2 of 2 — creating sign-in accounts</>');
        $this->line('  ' . str_repeat('─', 60));

        $accountArgs = [];
        if ($this->option('reset-passwords')) { $accountArgs['--reset'] = true; }

        Artisan::call('celeste:create-student-accounts', $accountArgs, $this->output);

        $after = [
            'records'  => StudentRecord::count(),
            'accounts' => User::where('role', User::ROLE_STUDENT)->count(),
        ];

        $this->newLine();
        $this->line('  <options=bold>Summary</>');
        $this->line('  ' . str_repeat('─', 60));
        $this->table(
            ['', 'Before', 'After', 'Added'],
            [
                ['Student records', $before['records'],  $after['records'],  $after['records']  - $before['records']],
                ['Sign-in accounts', $before['accounts'], $after['accounts'], $after['accounts'] - $before['accounts']],
            ]
        );

        // Records without an account are the failure that hides: the students
        // exist, documents can be issued to them, and none of them can sign in
        // to collect one.
        $orphaned = StudentRecord::whereNotExists(function ($q) {
            $q->selectRaw(1)->from('users')
              ->whereColumn('users.student_number', 'student_records.student_number');
        })->count();

        if ($orphaned > 0) {
            $this->newLine();
            $this->warn("  {$orphaned} student record(s) have no sign-in account.");
            $this->line('  Almost always a missing or non-institutional email address in the CSV.');
            $this->line('  The account step above lists which ones and why.');
        } else {
            $this->newLine();
            $this->info('  Every student record has a sign-in account.');
        }

        $this->newLine();
        $this->line('  Students sign in with their institutional email address.');
        $this->line('  Their password is their student number until they set their own.');
        $this->newLine();

        return self::SUCCESS;
    }
}
