<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One active certificate per student per document type.
 *
 * The application already checks, but a check in application code cannot stop
 * two requests arriving at the same instant, and does not apply to anything
 * that writes to the table directly. This makes the rule a property of the
 * data rather than a habit of the code.
 *
 * Partial, so it constrains only documents that are still in force — a
 * student may hold any number of revoked or superseded certificates, which is
 * the whole point of keeping them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS certificates_one_active_per_type
            ON certificates (student_record_id, document_type)
            WHERE status = 'issued'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS certificates_one_active_per_type');
    }
};