<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'birth_date', 'college', 'program', 'major', 'status', 'year_level',
        'academic_year', 'semester', 'date_admitted', 'date_graduated',
        'latin_honor', 'general_weighted_average', 'grades',
    ];

    protected function casts(): array
    {
        return [
            'grades'                   => 'array',
            'birth_date'               => 'date',
            'date_admitted'            => 'date',
            'date_graduated'           => 'date',
            'general_weighted_average' => 'decimal:3',
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
