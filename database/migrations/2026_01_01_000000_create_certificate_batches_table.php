<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // BATCH-2026-0007
            $table->string('label');
            $table->string('document_type');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('generated')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->json('errors')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_batches');
    }
};
