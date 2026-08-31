<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
