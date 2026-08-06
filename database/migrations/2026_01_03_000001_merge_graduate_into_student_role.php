<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Folds the graduate role into student.
 *
 * The two behaved identically: both reach the same dashboard, see the same
 * documents, and are restricted to their own records. Whether someone has
 * graduated is a property of their student record, not of their login, and
 * keeping it in two places invited them to disagree — an account marked
 * graduate whose record still says enrolled, or the reverse.
 *
 * Existing graduate accounts become student accounts. Nothing else about them
 * changes, and their certificates are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'graduate')->update(['role' => 'student']);

        // enum() on PostgreSQL is a varchar with a CHECK constraint, so the
        // constraint has to be replaced rather than the column redefined.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('student', 'registrar'))");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('student', 'graduate', 'registrar'))");
        }

        // Accounts are not split back apart; graduation status lives on the
        // student record, so nothing is lost by leaving them as students.
    }
};
