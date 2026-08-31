<?php

namespace App\Console\Commands;

use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateStudentAccounts extends Command
{
    protected $signature = 'celeste:create-student-accounts
                            {--dry-run : Report what would be created without writing}
                            {--reset : Reset existing accounts back to the student number}
                            {--student= : Only this student number}';

    protected $description = 'Create student sign-in accounts from student records';

    public function handle(): int
    {
        $domain = (string) config('celeste.institution.email_domain', 'parsu.edu.ph');

        $records = StudentRecord::query()
            ->when($this->option('student'), fn ($q) => $q->where('student_number', $this->option('student')))
            ->orderBy('last_name')
            ->get();

        if ($records->isEmpty()) {
            $this->error('  No student records found. Import them first.');

            return self::FAILURE;
        }

        $created = 0; $reset = 0; $skipped = 0;
        $missing = []; $foreign = []; $preview = [];

        foreach ($records as $record) {
            $email = mb_strtolower(trim((string) $record->email));

            if ($email === '') { $missing[] = $record->student_number; continue; }

            if (! $this->isInstitutional($email, $domain)) {
                $foreign[] = "{$record->student_number} → {$email}";
                continue;
            }

            $existing = User::where('email', $email)
                ->orWhere('student_number', $record->student_number)->first();

            if ($existing && ! $this->option('reset')) { $skipped++; continue; }

            if ($this->option('dry-run')) {
                $preview[] = [$record->student_number, $record->full_name, $email,
                              $existing ? 'password reset' : 'new account'];
                $existing ? $reset++ : $created++;
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'           => $record->full_name,
                    'username'       => $email,   
                    'password'       => Hash::make($record->student_number),   
                    'role'           => User::ROLE_STUDENT,
                    'student_number' => $record->student_number,
                    'college'        => $record->college,
                    'program'        => $record->program,
                    'is_active'      => true,
                    'password_changed_at' => null,
                ]
            );

            $existing ? $reset++ : $created++;
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            if ($preview !== []) {
                $this->table(['Student number', 'Name', 'Sign-in email', 'Action'], array_slice($preview, 0, 15));
                if (count($preview) > 15) { $this->line('    … and ' . (count($preview) - 15) . ' more.'); }
            }
            $this->info("  Would create {$created}, reset {$reset}, skip {$skipped}. Nothing was written.");
        } else {
            $this->info("  Created {$created}, reset {$reset}, skipped {$skipped}.");
            if ($skipped > 0) {
                $this->line('  Skipped accounts already exist. Use --reset to set their passwords back.');
            }
        }

        if ($missing !== []) {
            $this->newLine();
            $this->error('  ' . count($missing) . ' record(s) have no email address and were skipped:');
            $this->line('    ' . implode(', ', array_slice($missing, 0, 12)) . (count($missing) > 12 ? ' …' : ''));
            $this->line('    Add the email column to your CSV and re-run. Addresses are not guessed —');
            $this->line('    a wrong one creates an account the student can never sign in to.');
        }

        if ($foreign !== []) {
            $this->newLine();
            $this->error('  ' . count($foreign) . ' record(s) use a non-institutional address and were skipped:');
            foreach (array_slice($foreign, 0, 12) as $line) { $this->line('    ' . $line); }
            $this->line("    Only @{$domain} accounts can sign in.");
        }

        return self::SUCCESS;
    }

    private function isInstitutional(string $email, string $domain): bool
    {
        $at = strrpos($email, '@');

        return $at !== false && $domain !== ''
            && hash_equals(mb_strtolower($domain), mb_strtolower(substr($email, $at + 1)));
    }
}
