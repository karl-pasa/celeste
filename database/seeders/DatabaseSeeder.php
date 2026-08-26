<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds nothing.
 *
 * This system holds real academic records, so inventing students is not a
 * neutral convenience: fabricated people would sit in the same tables as
 * genuine ones, and the verification analytics would read partly fictional
 * traffic and report it as fact.
 *
 * The class remains because Laravel expects it — `db:seed` and
 * `migrate:fresh --seed` both resolve it by name. It simply inserts nothing
 * and prints the commands that set up a working system instead.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->newLine();
        $this->command->info('  No sample data was created — this system is for real records.');
        $this->command->newLine();
        $this->command->line('  To set up a working system:');
        $this->command->newLine();
        $this->command->line('    php artisan celeste:create-registrar');
        $this->command->line('    php artisan celeste:onboard storage/imports/new-students.csv --dry-run');
        $this->command->line('    php artisan celeste:onboard storage/imports/new-students.csv');
        $this->command->newLine();
    }
}