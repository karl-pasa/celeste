<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('college');
            $table->string('program');
            $table->string('major')->nullable();
            $table->enum('status', ['enrolled', 'graduated', 'transferred', 'inactive'])->default('enrolled');
            $table->string('year_level')->nullable();
            $table->string('academic_year')->nullable();
            $table->string('semester')->nullable();
            $table->date('date_admitted')->nullable();
            $table->date('date_graduated')->nullable();
            $table->string('latin_honor')->nullable();
            $table->decimal('general_weighted_average', 5, 3)->nullable();
            $table->json('grades')->nullable(); // subject rows used by the Transcript of Records
            $table->timestamps();

            $table->index(['college', 'program']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_records');
    }
};
