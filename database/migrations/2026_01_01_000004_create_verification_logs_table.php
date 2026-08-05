<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('submitted_reference')->nullable(); // what the verifier actually typed or scanned
            $table->enum('method', ['qr_scan', 'serial', 'hash', 'link'])->index();
            $table->enum('result', ['authentic', 'revoked', 'tampered', 'not_found'])->index();
            $table->string('document_type')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at', 'result']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');            // certificate.generated, certificate.revoked, auth.login
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('verification_logs');
    }
};
