<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves sign-in from username to email address.
 *
 * The username column is kept rather than dropped: other screens read it, and
 * removing a NOT NULL unique column is harder to reverse than leaving it in
 * place. It is backfilled with the email so the two never disagree, and made
 * nullable so future accounts need not set it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE users SET username = email WHERE username IS DISTINCT FROM email');

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET username = split_part(email, '@', 1) WHERE username IS NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }
};
