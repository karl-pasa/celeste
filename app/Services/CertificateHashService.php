<?php

namespace App\Services;

use App\Models\Certificate;

/**
 * Produces the cryptographic fingerprint that makes a CELESTE certificate tamper-evident.
 *
 * The hash is taken over a *canonical* representation of the payload: keys sorted,
 * values normalised, then serialised as compact JSON. Sorting matters — without it
 * two identical documents whose fields happened to be inserted in a different order
 * would produce different hashes and every verification would fail.
 *
 * A server-side pepper (CELESTE_HASH_PEPPER) is mixed in through HMAC, so an attacker
 * who knows the printed fields still cannot forge a matching hash without the key.
 */
class CertificateHashService
{
    public function canonicalise(array $payload): string
    {
        return json_encode(
            $this->normalise($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Recursively sort keys and trim scalar values so the encoding is deterministic.
     */
    protected function normalise(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalise($value);
            } elseif (is_string($value)) {
                $data[$key] = trim(preg_replace('/\s+/u', ' ', $value));
            } elseif ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d');
            } elseif (is_float($value)) {
                $data[$key] = number_format($value, 3, '.', '');
            }
        }

        return $data;
    }

    public function hash(array $payload): string
    {
        return hash_hmac(
            'sha256',
            $this->canonicalise($payload),
            (string) config('celeste.hash_pepper')
        );
    }

    public function hashFile(string $binary): string
    {
        return hash('sha256', $binary);
    }

    /**
     * Recompute the stored payload's hash and compare in constant time.
     * A mismatch means the database row was edited outside the application.
     */
    public function matches(Certificate $certificate): bool
    {
        return hash_equals(
            $certificate->content_hash,
            $this->hash($certificate->payload ?? [])
        );
    }

    public function verifyFile(Certificate $certificate, string $binary): bool
    {
        if (! $certificate->file_hash) {
            return true; // no stored file fingerprint to compare against
        }

        return hash_equals($certificate->file_hash, $this->hashFile($binary));
    }
}
