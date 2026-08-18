<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks every existing account as verified.
 *
 * Email verification is being removed for students: accounts are provisioned
 * by the Registrar from records the University already holds, so the address
 * is established by the institution rather than claimed by the person. A
 * verification round-trip adds a delivery dependency without adding
 * assurance, and locks students out of a system that may have no mail driver
 * configured at all.
 *
 * Backfilling rather than only removing the middleware means any check that
 * survives elsewhere — now or in future code — passes rather than silently
 * stranding a student at a screen with no way forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_verified_at')) {
            return;
        }

        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Deliberately empty. Clearing the timestamps would re-lock every
        // student out of the system, and the column carries no information
        // worth restoring — nobody verified anything to earn it.
    }
};
