<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Human-readable serial printed on the document, e.g. PSU-DIP-2026-000184
            $table->string('serial_number')->unique();

            // Opaque public token used in the QR URL so serials cannot be enumerated
            $table->string('verification_token', 64)->unique();

            $table->enum('document_type', [
                'diploma',
                'honorable_dismissal',
                'certificate_of_enrolment',
                'transcript_of_records',
            ])->index();

            $table->foreignId('student_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users');
            $table->foreignId('batch_id')->nullable()->constrained('certificate_batches')->nullOnDelete();

            // Canonical snapshot of every field printed on the document.
            // The hash is computed over this payload, so any later edit is detectable.
            $table->json('payload');
            $table->char('content_hash', 64)->index();   // SHA-256 of canonical payload
            $table->char('file_hash', 64)->nullable();   // SHA-256 of the rendered PDF bytes

            $table->string('file_path')->nullable();
            $table->string('qr_path')->nullable();

            $table->enum('status', ['issued', 'revoked', 'superseded'])->default('issued')->index();
            $table->text('revocation_reason')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('certificates')->nullOnDelete();

            $table->date('issued_on');
            $table->unsignedInteger('verification_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'status']);
            $table->index('issued_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
