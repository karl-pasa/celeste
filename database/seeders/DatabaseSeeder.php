<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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