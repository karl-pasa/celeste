<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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