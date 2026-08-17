<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT   = 'student';
    public const ROLE_REGISTRAR = 'registrar';

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role',
        'student_number', 'college', 'program', 'is_active',
    ];

    /**
     * Never serialised. remember_token is a bearer credential in its own
     * right, so it is hidden alongside the password hash.
     */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            // Hashing happens on assignment, so a plaintext password can
            // never be written to the column even by mistake.
            'password'            => 'hashed', // bcrypt cost 12, config/hashing.php
            'is_active'           => 'boolean',
            'last_login_at'       => 'datetime',
            'email_verified_at'   => 'datetime',
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

    /**
     * True while the account is still on its provisioned credential. For a
     * student that is their student number, which is printed on every
     * document this system issues.
     */
    public function isUsingInitialPassword(): bool
    {
        return $this->password_changed_at === null;
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
