<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the Registrar account.
 *
 * The password is typed at the terminal rather than passed as an argument or
 * read from a file: an argument lands in shell history and a file lands in
 * version control, and this account can issue and revoke academic credentials
 * for the whole university.
 */
class CreateRegistrar extends Command
{
    protected $signature = 'celeste:create-registrar
                            {--name= : Full name}
                            {--email= : Institutional email address}';

    protected $description = 'Create a registrar account interactively';

    public function handle(): int
    {
        $domain = (string) config('celeste.institution.email_domain', 'parsu.edu.ph');
        $suggested = (string) config('celeste.institution.registrar_email', "registrar@{$domain}");

        $this->newLine();
        $this->line('  <options=bold>Create a registrar account</>');
        $this->line('  This account can generate, revoke and reissue documents.');
        $this->newLine();

        $name = $this->option('name') ?: $this->ask('  Full name, as it should appear in the system');
        $email = mb_strtolower(trim($this->option('email') ?: $this->ask('  Institutional email address', $suggested)));

        $password = $this->secret('  Password (at least 10 characters, mixed case and a number)');
        $confirm  = $this->secret('  Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name'     => ['required', 'string', 'min:2', 'max:120'],
                'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            ],
            ['email.unique' => 'An account already uses that address.',
             'password.confirmed' => 'The two passwords do not match.']
        );

        if ($validator->fails()) {
            $this->newLine();
            foreach ($validator->errors()->all() as $e) { $this->error('  ' . $e); }
            $this->newLine();

            return self::FAILURE;
        }

        if (! $this->isInstitutional($email, $domain)) {
            $this->newLine();
            $this->error("  Registrar accounts must use an @{$domain} address.");
            $this->newLine();

            return self::FAILURE;
        }

        // Refuse the obvious: the address itself, or a sample credential from
        // the setup documentation.
        $weak = [mb_strtolower(explode('@', $email)[0]), 'registrar123', 'password123', 'parsu12345'];

        if (in_array(mb_strtolower($password), $weak, true)) {
            $this->newLine();
            $this->error('  That password is part of your address or a sample credential. Choose another.');
            $this->newLine();

            return self::FAILURE;
        }

        $user = User::create([
            'name'      => $name,
            'username'  => $email,   // kept in step; sign-in uses email
            'email'     => $email,
            'password'  => Hash::make($password),   // bcrypt cost 12
            'role'      => User::ROLE_REGISTRAR,
            'is_active' => true,
            // Provisioned in person at a console by someone with server
            // access, so the change-password and verification gates would add
            // a failure mode without adding assurance.
            'password_changed_at' => now(),
            'email_verified_at'   => now(),
        ]);

        AuditLog::record('account.registrar_created', $user, ['created_via' => 'console']);

        $this->newLine();
        $this->info("  Registrar account created for {$name}.");
        $this->line("  Sign in on the <options=bold>Registrar</> tab with: {$email}");
        $this->newLine();

        if (User::where('role', User::ROLE_REGISTRAR)->count() > 1) {
            $this->warn('  More than one registrar account exists. Review them:');
            $this->line("    php artisan tinker");
            $this->line("    >>> App\\Models\\User::where('role','registrar')->get(['id','email'])");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function isInstitutional(string $email, string $domain): bool
    {
        $at = strrpos($email, '@');

        return $at !== false && $domain !== ''
            && hash_equals(mb_strtolower($domain), mb_strtolower(substr($email, $at + 1)));
    }
}
