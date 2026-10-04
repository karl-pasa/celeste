<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal details from the Student Personal Record form.
 *
 * Address is held in parts rather than as one line. The registrar's form
 * asks for province, city, barangay and ZIP separately, and a record kept
 * that way can be sorted and counted by locality later; a single free-text
 * line cannot. The existing `address` column stays and is composed from the
 * permanent parts when a record is saved, because the Transfer Credential
 * template prints it from the hashed payload and must keep working.
 *
 * Each column is added only if missing, so the migration is safe to run
 * against a database where some were already added by hand.
 */
return new class extends Migration
{
    /** @var array<string, int> column => length */
    protected array $columns = [
        // personal
        'contact_number' => 30,
        'civil_status'   => 30,
        'religion'       => 100,

        // permanent address
        'perm_province'  => 100,
        'perm_city'      => 100,
        'perm_barangay'  => 100,
        'perm_zip'       => 10,

        // temporary address
        'temp_province'  => 100,
        'temp_city'      => 100,
        'temp_barangay'  => 100,
        'temp_zip'       => 10,

        // person to contact in an emergency
        'emergency_name'           => 150,
        'emergency_contact_number' => 30,
        'emergency_address'        => 255,
        'emergency_relationship'   => 50,
    ];

    public function up(): void
    {
        foreach ($this->columns as $name => $length) {
            if (Schema::hasColumn('student_records', $name)) {
                continue;
            }

            Schema::table('student_records',
                function (Blueprint $table) use ($name, $length) {
                    $table->string($name, $length)->nullable();
                });
        }
    }

    public function down(): void
    {
        $present = array_filter(
            array_keys($this->columns),
            fn ($name) => Schema::hasColumn('student_records', $name),
        );

        if ($present === []) {
            return;
        }

        Schema::table('student_records', function (Blueprint $table) use ($present) {
            $table->dropColumn($present);
        });
    }
};
