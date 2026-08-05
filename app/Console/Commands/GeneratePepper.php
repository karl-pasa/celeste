<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GeneratePepper extends Command
{
    protected $signature = 'celeste:pepper';

    protected $description = 'Generate a hash pepper for CELESTE_HASH_PEPPER';

    public function handle(): int
    {
        $pepper = Str::random(64);

        $this->newLine();
        $this->line('  Add this to your .env file:');
        $this->newLine();
        $this->line("  CELESTE_HASH_PEPPER={$pepper}");
        $this->newLine();
        $this->warn('  Set it once. Changing it later invalidates every certificate already issued.');
        $this->newLine();

        return self::SUCCESS;
    }
}
