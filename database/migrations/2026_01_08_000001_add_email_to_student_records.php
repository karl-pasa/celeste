<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the institutional email address to student records.
 *
 * Sign-in uses this address, so it has to come from the Registrar's data
 * rather than be derived. The university's format —
 * jdelacruz922.pbox@parsu.edu.ph — combines an initial, a surname, digits and
 * a mailbox suffix, none of which is reconstructable from a name and a
 * student number. Guessing wrong produces an account at a mailbox the student
 * cannot reach: they can never sign in, verify, or reset.
 *
 * Nullable, because a record without an address simply has no account yet.
 * Unique, because two students cannot share one sign-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            if (! Schema::hasColumn('student_records', 'email')) {
                $table->string('email')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            if (Schema::hasColumn('student_records', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
