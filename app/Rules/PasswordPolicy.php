<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * The password rules applied wherever a password is set or changed.
 *
 * Kept in one place so reset, first-login change and any future admin reset
 * cannot drift apart — a policy enforced on one path and not another is not a
 * policy.
 */
class PasswordPolicy
{
    public static function rules(): array
    {
        return [
            'required',
            'confirmed',
            Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers()
                // Checks the candidate against known breach corpora using
                // k-anonymity: only a five-character hash prefix leaves the
                // server, never the password.
                ->uncompromised(),
        ];
    }

    /**
     * Values that must never be accepted, whatever else they satisfy.
     *
     * A student number is printed on ID cards and on every document this
     * system issues, so anyone holding a transcript can read it. It is a
     * usable first-login credential and an indefensible standing one —
     * refusing it here is the point of asking for a change at all.
     */
    public static function forbidden(User $user): array
    {
        return array_values(array_filter([
            $user->student_number,
            $user->username,
            $user->email,
            explode('@', (string) $user->email)[0] ?: null,
        ]));
    }

    public static function messages(): array
    {
        return [
            'password.confirmed'      => 'The two new passwords do not match.',
            'password.min'            => 'Use at least 10 characters.',
            'password.mixed'          => 'Include both upper and lower case letters.',
            'password.numbers'        => 'Include at least one number.',
            'password.uncompromised'  => 'That password appears in a known data breach. Choose another.',
        ];
    }
}
