<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the section a student belongs to.
 *
 * Kept as a plain string rather than a foreign key to a sections table: a
 * section is written on the record as it was at the time, and a student who
 * moves from 3-A to 3-B should not have their earlier documents re-read as
 * though they had always been in 3-B. The list of selectable values lives in
 * config/celeste.php, so adding one needs no migration.
 *
 * Guarded with hasColumn because the column may already have been added by
 * hand in the Supabase SQL editor while the local connection was failing.
 * Without the guard the migration throws a duplicate column error, never
 * records itself, and fails again on every subsequent run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('student_records', 'section')) {
            return;
        }

        Schema::table('student_records', function (Blueprint $table) {
            $table->string('section', 20)->nullable()->after('year_level');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('student_records', 'section')) {
            return;
        }

        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
