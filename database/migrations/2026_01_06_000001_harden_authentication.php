<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the hardened authentication path depends on.
 *
 * password_changed_at drives the forced first-login change: while it is null
 * the account is still on its provisioned credential, which for students is
 * their student number and therefore public.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }
        });

        // Registrar accounts are provisioned in person at a console, so mail
        // delivery adds a failure mode without adding assurance.
        DB::table('users')->where('role', 'registrar')->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'password_changed_at']);
        });
    }
};
