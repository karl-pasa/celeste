<?php

namespace App\Livewire\Account;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Account settings: profile details and password change.
 *
 * Two separate forms rather than one. Saving a display name should not require
 * typing a password, and changing a password should not silently carry along
 * an edit to another field the person had forgotten they made.
 */
class ProfileSettings extends Component
{
    // --- Profile -----------------------------------------------------------
    public string $name = '';
    public string $email = '';
    public string $username = '';

    // --- Password ----------------------------------------------------------
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    /**
     * A student's email is their sign-in identity and comes from the registrar's
     * records, so it is not theirs to edit. Letting them change it here would
     * put the account and the student record out of step.
     */
    public function getCanEditEmailProperty(): bool
    {
        return $this->user->isRegistrar();
    }

    /**
     * Students are provisioned with their student number as the initial
     * password. It is printed on ID cards and on every document the system
     * issues, so it identifies the account rather than protecting it.
     */
    public function getUsingInitialPasswordProperty(): bool
    {
        $user = $this->user;

        if ($user->isRegistrar() || ! $user->student_number) {
            return false;
        }

        return Hash::check($user->student_number, $user->password);
    }

    public function saveProfile(): void
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ];

        if ($this->canEditEmail) {
            $rules['email'] = ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)];
            $rules['username'] = ['required', 'string', 'min:3', 'max:60', Rule::unique('users', 'username')->ignore($user->id)];
        }

        $validated = $this->validate($rules, [
            'email.unique'    => 'Another account already uses that email address.',
            'username.unique' => 'That username is taken.',
        ]);

        $user->update($validated);

        AuditLog::record('account.profile_updated', $user);

        $this->dispatch('profile-saved');
        session()->flash('profile-status', 'Your details have been saved.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
                // A student number is public: it appears on ID cards and on
                // every issued document. Refusing it here is the point of
                // asking people to change from the initial password at all.
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->student_number && trim($value) === trim($user->student_number)) {
                        $fail('Your student number is printed on your documents, so it cannot be your password.');
                    }

                    if (Hash::check($value, $user->password)) {
                        $fail('That is your current password. Choose a different one.');
                    }
                },
            ],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed'                => 'The two new passwords do not match.',
        ]);

        $user->forceFill([
            'password'            => Hash::make($this->password),
            'password_changed_at' => now(),
        ])->save();

        AuditLog::record('account.password_changed', $user);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('password-status', 'Your password has been changed.');
    }

    public function render()
    {
        return view('livewire.account.profile-settings');
    }
}