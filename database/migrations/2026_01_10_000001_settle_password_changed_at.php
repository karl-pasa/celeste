<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settles password_changed_at on every account.
 *
 * The student number is the student's password by design — issued by the
 * Registrar, the same for the life of the account, and not something the
 * holder is expected to replace. A null in this column meant "still on the
 * provisioned credential, must change"; with the forced change removed, that
 * distinction no longer describes anything true about the account.
 *
 * Backfilling rather than only unregistering the middleware means any check
 * that survives elsewhere — now or in future code — passes rather than
 * stranding a student at a screen with no way forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_changed_at')) {
            return;
        }

        DB::table('users')->whereNull('password_changed_at')->update([
            'password_changed_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Deliberately empty. Clearing these would send every student back to
        // a change-password screen that no longer has a purpose.
    }
};
