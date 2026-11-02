<?php

namespace App\Services;

use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Creates and maintains student logins for the administrator.
 *
 * Email    : initials of the first name + last name + last 3 digits of the
 *            student number + .pbox@parsu.edu.ph
 *            (Juan Carlo Dela Cruz, 2023-12345 -> jcdelacruz345.pbox@parsu.edu.ph)
 * Password : the student number. password_changed_at stays empty, so the
 *            system makes the student choose a new password at first sign-in.
 */
class StudentLoginManager
{
    public const CREATED = 'created';
    public const EXISTS  = 'exists';
    public const TAKEN   = 'taken';
    public const BAD     = 'bad';

    public function domain(): string
    {
        return mb_strtolower((string) config('celeste.institution.email_domain', 'parsu.edu.ph'));
    }

    /** The login email for a record, or null when the name or number is unusable. */
    public function emailFor(StudentRecord $record): ?string
    {
        $first = strtolower(Str::ascii((string) $record->first_name));
        $words = preg_split('/[^a-z]+/', $first, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = implode('', array_map(fn ($word) => $word[0], $words));

        $last = preg_replace('/[^a-z]/', '', strtolower(Str::ascii((string) $record->last_name)));
        $digits = substr(preg_replace('/\D/', '', (string) $record->student_number), -3);

        if ($initials === '' || $last === '' || strlen($digits) < 3) {
            return null;
        }

        return $initials . $last . $digits . '.pbox@' . $this->domain();
    }

    public function create(StudentRecord $record): string
    {
        $studentNumber = (string) $record->student_number;

        if ($studentNumber === '') {
            return self::BAD;
        }

        if (User::where('student_number', $studentNumber)->where('role', User::ROLE_STUDENT)->exists()) {
            return self::EXISTS;
        }

        $email = $this->emailFor($record);

        if ($email === null) {
            return self::BAD;
        }

        if (User::where('email', $email)->exists()) {
            return self::TAKEN;
        }

        $attributes = [
            'name'              => trim($record->first_name . ' ' . $record->last_name),
            'email'             => $email,
            'password'          => $studentNumber,   // hashed by the model's cast
            'role'              => User::ROLE_STUDENT,
            'student_number'    => $studentNumber,
            'college'           => $record->college,
            'program'           => $record->program,
            'is_active'         => true,
            'email_verified_at' => now(),
        ];

        if (Schema::hasColumn('users', 'username')) {
            $attributes['username'] = $studentNumber;
        }

        try {
            (new User)->forceFill($attributes)->save();
        } catch (QueryException) {
            return self::TAKEN;
        }

        // Keep the record's email the same as the login email.
        StudentRecord::whereKey($record->getKey())->update(['email' => $email]);

        return self::CREATED;
    }

    /** Back to the first password, and the student must choose a new one again. */
    public function resetPassword(User $user): void
    {
        $user->forceFill([
            'password'            => (string) $user->student_number,
            'password_changed_at' => null,
            'remember_token'      => null,
        ])->save();
    }
}