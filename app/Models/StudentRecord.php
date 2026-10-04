<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        // identity
        'student_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'gender', 'civil_status', 'birth_date', 'birthplace', 'nationality',
        'religion', 'email', 'contact_number', 'address',

        // permanent address
        'perm_province', 'perm_city', 'perm_barangay', 'perm_zip',

        // temporary address
        'temp_province', 'temp_city', 'temp_barangay', 'temp_zip',

        // person to contact in an emergency
        'emergency_name', 'emergency_contact_number',
        'emergency_address', 'emergency_relationship',

        // academic
        'college', 'program', 'major', 'status',
        'year_level', 'section', 'academic_year', 'semester',
        'general_weighted_average', 'grades',

        // admission
        'admission_type', 'date_admitted',
        'adm_new_school', 'adm_new_address', 'adm_new_course',
        'adm_new_year_graduated',
        'adm_tr_school', 'adm_tr_address', 'adm_tr_course',
        'adm_tr_year_graduated', 'adm_tr_credential',

        // graduation
        'date_graduated', 'date_conferred', 'board_resolution_no',
        'board_resolution_date', 'latin_honor', 'awards',

        // other
        'nstp_serial_no', 'program_accreditation',
        'granted_transfer_credentials', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'grades'                   => 'array',
            'birth_date'               => 'date',
            'date_admitted'            => 'date',
            'date_graduated'           => 'date',
            'general_weighted_average' => 'decimal:3',
            'date_conferred'        => 'date',
            'board_resolution_date' => 'date',
        ];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . mb_substr($this->middle_name, 0, 1) . '.' : '';

        return trim("{$this->first_name}{$middle} {$this->last_name} {$this->suffix}");
    }

    public function getFormalNameAttribute(): string
    {
        return trim(mb_strtoupper("{$this->first_name} {$this->middle_name} {$this->last_name} {$this->suffix}"));
    }

    public function totalUnits(): float
    {
        return collect($this->grades ?? [])->sum(fn ($row) => (float) ($row['units'] ?? 0));
    }
}
