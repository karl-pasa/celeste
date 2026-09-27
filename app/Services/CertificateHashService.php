<?php

namespace App\Services;

use App\Models\Certificate;

class CertificateHashService
{
    public function canonicalise(array $payload): string
    {
        return json_encode(
            $this->normalise($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function canonicalArray(array $payload): array
    {
        return (array) json_decode($this->canonicalise($payload), true);
    }

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
            } elseif (is_int($value)) {
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
