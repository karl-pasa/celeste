<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT   = 'student';
    public const ROLE_REGISTRAR = 'registrar';

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role',
        'student_number', 'college', 'program', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'            => 'hashed', // bcrypt via config/hashing.php
            'is_active'           => 'boolean',
            'last_login_at'       => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function studentRecord(): HasOne
    {
        return $this->hasOne(StudentRecord::class, 'student_number', 'student_number');
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by');
    }

    public function isRegistrar(): bool
    {
        return $this->role === self::ROLE_REGISTRAR;
    }

    public function isLearner(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_REGISTRAR => 'Registrar',
            default              => 'Student',
        };
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}