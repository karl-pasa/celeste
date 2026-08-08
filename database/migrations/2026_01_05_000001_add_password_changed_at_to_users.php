<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records when a password was last changed.
 *
 * Students are provisioned with their student number as an initial password.
 * That number is printed on ID cards and on every document the system issues,
 * so it identifies the account rather than protecting it. Knowing whether it
 * has ever been changed is what lets the account page say so plainly — and
 * what a future first-login enforcement would read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};