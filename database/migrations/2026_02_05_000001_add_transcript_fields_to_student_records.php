<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            // ---- Personal information ----
            if (! Schema::hasColumn('student_records', 'gender')) {
                $table->string('gender', 20)->nullable();
            }
            if (! Schema::hasColumn('student_records', 'nationality')) {
                $table->string('nationality', 60)->nullable();
            }
            if (! Schema::hasColumn('student_records', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
            if (! Schema::hasColumn('student_records', 'birthplace')) {
                $table->string('birthplace', 150)->nullable();
            }

            // ---- Admission data · A. NEW ----
            $table->string('adm_new_school', 150)->nullable();
            $table->string('adm_new_address', 200)->nullable();
            $table->string('adm_new_course', 150)->nullable();
            $table->string('adm_new_year_graduated', 20)->nullable();

            // ---- Admission data · TRANSFEREE ----
            $table->string('adm_tr_school', 150)->nullable();
            $table->string('adm_tr_address', 200)->nullable();
            $table->string('adm_tr_course', 150)->nullable();
            $table->string('adm_tr_year_graduated', 20)->nullable();
            $table->string('adm_tr_credential', 150)->nullable();

            // Which of the two blocks applies to this student.
            $table->string('admission_type', 20)->nullable();

            // ---- Admission data · B ----
            // date_admitted already exists and carries this value.

            // ---- Graduation data ----
            $table->date('date_conferred')->nullable();
            $table->string('board_resolution_no', 60)->nullable();
            $table->date('board_resolution_date')->nullable();
            $table->string('awards', 150)->nullable();

            // ---- Other printed fields ----
            $table->string('nstp_serial_no', 60)->nullable();
            $table->string('program_accreditation', 200)->nullable();
            $table->text('granted_transfer_credentials')->nullable();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn([
                'adm_new_school', 'adm_new_address', 'adm_new_course', 'adm_new_year_graduated',
                'adm_tr_school', 'adm_tr_address', 'adm_tr_course', 'adm_tr_year_graduated',
                'adm_tr_credential', 'admission_type',
                'date_conferred', 'board_resolution_no', 'board_resolution_date', 'awards',
                'nstp_serial_no', 'program_accreditation',
                'granted_transfer_credentials', 'remarks',
            ]);
        });
    }
};
