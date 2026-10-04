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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->string('section', 20)->nullable()->after('year_level');
        });
    }

    public function down(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
