<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\CertificateHashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Explains why a certificate's fingerprint does not match its payload.
 *
 * "Mismatch" only means the stored hash differs from a fresh hash of the
 * stored payload. That has several possible causes with very different
 * consequences, and guessing between them is how a harmless type-conversion
 * bug gets mistaken for tampering. This reports which one it is.
 */
class CheckIntegrity extends Command
{
    protected $signature = 'celeste:check-integrity
                            {serial? : A certificate serial, otherwise every certificate}';

    protected $description = 'Compare each stored fingerprint against a fresh hash of its payload';

    public function handle(CertificateHashService $hasher): int
    {
        $certificates = $this->argument('serial')
            ? Certificate::where('serial_number', $this->argument('serial'))->get()
            : Certificate::orderBy('id')->get();

        if ($certificates->isEmpty()) {
            $this->error('  No certificates found.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <options=bold>Pepper</>');

        // A pepper that has changed since issuance breaks every certificate at
        // once, so it is worth ruling out before examining any single one.
        $pepper = (string) config('celeste.hash_pepper', '');
        $this->line('     configured : ' . ($pepper === '' ? 'EMPTY' : 'set, ' . strlen($pepper) . ' chars'));
        $this->line('     fingerprint: ' . ($pepper === '' ? '—' : substr(hash('sha256', $pepper), 0, 16)));

        if ($pepper === '') {
            $this->newLine();
            $this->error('  The pepper is empty. Every fingerprint computed with a different value');
            $this->error('  will fail, and this is almost certainly the cause.');
            $this->line('  Check CELESTE_HASH_PEPPER in .env, then run php artisan config:clear.');
        }

        $ok = 0; $bad = 0;

        foreach ($certificates as $certificate) {
            $stored = (string) $certificate->content_hash;
            $fresh  = $hasher->hash($certificate->payload);

            if (hash_equals($stored, $fresh)) {
                $ok++;
                continue;
            }

            $bad++;
            $this->newLine();
            $this->error("  MISMATCH  {$certificate->serial_number}");
            $this->line('     stored : ' . substr($stored, 0, 32) . '…');
            $this->line('     fresh  : ' . substr($fresh, 0, 32) . '…');

            $this->explain($certificate, $hasher);
        }

        $this->newLine();
        $this->line('  ' . str_repeat('─', 60));
        $this->info("  {$ok} verified");

        if ($bad > 0) {
            $this->error("  {$bad} mismatched");
        }

        $this->newLine();

        return $bad === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Work out which of the usual causes applies to this certificate.
     */
    private function explain(Certificate $certificate, CertificateHashService $hasher): void
    {
        // The payload the model hands back has been through Eloquent's array
        // cast. Comparing it against the raw column shows whether the database
        // round trip changed anything — the commonest cause, and an entirely
        // innocent one.
        $rawJson = DB::table('certificates')
            ->where('id', $certificate->id)
            ->value('payload');

        $raw = is_string($rawJson) ? json_decode($rawJson, true) : $rawJson;
        $cast = $certificate->payload;

        $rawHash = $hasher->hash((array) $raw);

        if (hash_equals((string) $certificate->content_hash, $rawHash)) {
            $this->newLine();
            $this->warn('     Cause: the array cast differs from the stored column.');
            $this->line('     The raw JSON hashes correctly; the value Eloquent hands back does not.');
            $this->line('     This is a type-conversion difference, not tampering.');

            return;
        }

        // Key order: JSONB does not preserve it. If canonicalisation does not
        // sort keys, the hash changes the moment the payload is written.
        $sorted = $cast;
        $this->recursiveSort($sorted);

        if (hash_equals((string) $certificate->content_hash, $hasher->hash($sorted))) {
            $this->newLine();
            $this->warn('     Cause: key order.');
            $this->line('     Sorting the keys reproduces the stored hash, so canonicalisation');
            $this->line('     is not sorting them. PostgreSQL JSONB reorders keys on write, so');
            $this->line('     the payload read back is never in the order it was hashed in.');

            return;
        }

        // Numeric types: 3 and 3.0 and "3" all serialise differently.
        $normalised = json_decode(json_encode($cast), true);

        if (hash_equals((string) $certificate->content_hash, $hasher->hash((array) $normalised))) {
            $this->newLine();
            $this->warn('     Cause: numeric types.');
            $this->line('     A JSON round trip reproduces the stored hash, so a value changed');
            $this->line('     type between hashing and storing — an integer becoming a float,');
            $this->line('     or a numeric string becoming a number.');

            return;
        }

        // Nothing benign explains it.
        $this->newLine();
        $this->error('     Cause: the payload content itself differs.');
        $this->line('     None of the type or ordering explanations reproduce the stored hash,');
        $this->line('     so the stored values are not what was hashed at issuance. Either the');
        $this->line('     payload was edited after the fact, or the pepper has changed.');

        $this->newLine();
        $this->line('     Payload keys: ' . implode(', ', array_keys((array) $cast)));
    }

    private function recursiveSort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }
    }
}
