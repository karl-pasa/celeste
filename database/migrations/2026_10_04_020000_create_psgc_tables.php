<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Philippine Standard Geographic Code reference tables.
 *
 * Held locally rather than fetched from an API when a form is opened. The
 * registrar should be able to add a student record whether or not some
 * third party's service is up, and 42,000 barangays is a trivial amount of
 * data for Postgres to hold and index.
 *
 * Populated by `php artisan celeste:import-psgc`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ph_provinces', function (Blueprint $table) {
            // The PSA code is the natural key and is stable across releases,
            // so it is the primary key rather than an auto-increment id.
            $table->string('code', 12)->primary();
            $table->string('name', 120)->index();
            $table->string('region_code', 12)->nullable();
            $table->string('region_name', 120)->nullable();
        });

        Schema::create('ph_cities', function (Blueprint $table) {
            $table->string('code', 12)->primary();
            $table->string('province_code', 12)->index();
            $table->string('name', 120)->index();
            // city or municipality -- the registrar's form says
            // "City / Municipality", so both appear in one list.
            $table->string('type', 20)->nullable();
        });

        Schema::create('ph_barangays', function (Blueprint $table) {
            $table->string('code', 12)->primary();
            $table->string('city_code', 12)->index();
            $table->string('name', 160);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ph_barangays');
        Schema::dropIfExists('ph_cities');
        Schema::dropIfExists('ph_provinces');
    }
};
