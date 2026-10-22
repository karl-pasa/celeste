<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('curriculum_subjects', function (Blueprint $t) {
            $t->id();
            $t->string('program_code', 20);          // BSIT, BSCS, BSCE ...
            $t->unsignedTinyInteger('year_level');   // 1-4
            $t->string('term', 10);                  // 1st | 2nd | Summer
            $t->string('code', 30);                  // CC1, IM1, NSTP1 ...
            $t->string('title');
            $t->decimal('unit', 3, 1);
            $t->decimal('lec', 3, 1)->default(0);
            $t->decimal('lab', 3, 1)->default(0);    // lab HOURS as printed on the program of study
            $t->decimal('ch', 3, 1)->default(0);
            $t->string('prereq')->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();

            $t->unique(['program_code', 'year_level', 'term', 'code']);
            $t->index(['program_code', 'year_level', 'term']);
        });
    }

    public function down(): void { Schema::dropIfExists('curriculum_subjects'); }
};
