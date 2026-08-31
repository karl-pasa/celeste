<?php

namespace App\Services;

use App\Models\Certificate;

/**
 * Produces the cryptographic fingerprint that makes a CELESTE certificate
 * tamper-evident.
 *
 * The hash is taken over a canonical representation of the payload: keys
 * sorted, values normalised, then serialised as compact JSON. Sorting matters
 * — without it two identical documents whose fields happened to be inserted in
 * a different order would produce different hashes and every verification
 * would fail.
 *
 * A server-side pepper (CELESTE_HASH_PEPPER) is mixed in through HMAC, so an
 * attacker who knows the printed fields still cannot forge a matching hash
 * without the key.
 *
 * ---------------------------------------------------------------------------
 * Why canonicalArray() exists
 * ---------------------------------------------------------------------------
 * normalise() does not only sort — it converts values. A float becomes a
 * fixed-precision string; a Carbon instance becomes a date string. Those
 * conversions are what make the encoding deterministic.
 *
 * But they applied only when hashing. The payload written to the database was
 * the untransformed original, so a float stored as the JSON number 21.0 came
 * back as a float and hashed to "21.000", while the value hashed at issuance
 * had already been converted. Two different inputs, two different hashes, and
 * a certificate that failed its own integrity check the moment it was created.
 *
 * canonicalArray() returns the same normalised structure as an array, so the
 * generator can store exactly what it hashed. After that the payload is a
 * fixed point: normalising it again changes nothing, so a hash taken now and a
 * hash taken in five years agree.
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
     * The canonical payload as an array — what should be persisted, so that
     * the stored row and the hashed input are the same thing.
     */
    public function canonicalArray(array $payload): array
    {
        return (array) json_decode($this->canonicalise($payload), true);
    }

    /**
     * Recursively sort keys and normalise scalar values so the encoding is
     * deterministic.
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
                // A date object serialises to JSON as an object of date,
                // timezone_type and timezone — nothing like the string this
                // produces. Converting here and storing the object elsewhere
                // is what broke the round trip.
                $data[$key] = $value->format('Y-m-d');
            } elseif (is_float($value)) {
                // Fixed precision, as a string, so 21.0 and 21 and "21.000"
                // all reduce to one representation.
                $data[$key] = number_format($value, 3, '.', '');
            } elseif (is_int($value)) {
                // Integers reach the same representation as floats, because
                // JSON does not distinguish them on the way back: a payload
                // holding 21 and one holding 21.0 must not hash differently.
                $data[$key] = number_format((float) $value, 3, '.', '');
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
