<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    public function __construct(protected CertificateHashService $hasher) {}

    public const AUTHENTIC = 'authentic';
    public const REVOKED   = 'revoked';
    public const TAMPERED  = 'tampered';
    public const NOT_FOUND = 'not_found';

    /**
     * @return array{result:string, certificate:?Certificate, message:string}
     */
    public function verify(string $reference, string $method, ?Request $request = null): array
    {
        $reference   = trim($reference);
        $certificate = $this->resolve($reference);

        $outcome = match (true) {
            ! $certificate                          => $this->outcome(self::NOT_FOUND, null),
            ! $this->hasher->matches($certificate)  => $this->outcome(self::TAMPERED, $certificate),
            $certificate->status === 'revoked'      => $this->outcome(self::REVOKED, $certificate),
            $certificate->status === 'superseded'   => $this->outcome(self::REVOKED, $certificate),
            default                                 => $this->outcome(self::AUTHENTIC, $certificate),
        };

        $this->log($reference, $method, $outcome, $request);

        if ($outcome['result'] === self::AUTHENTIC) {
            $this->recordVerification($certificate);
        }

        return $outcome;
    }
    protected function recordVerification(Certificate $certificate): void
    {
        DB::table('certificates')
            ->where('id', $certificate->id)
            ->update([
                'verification_count' => DB::raw('COALESCE(verification_count, 0) + 1'),
                'last_verified_at'   => now(),
            ]);
        $certificate->forceFill([
            'verification_count' => ($certificate->verification_count ?? 0) + 1,
            'last_verified_at'   => now(),
        ])->syncOriginal();
    }
    public function resolve(string $reference): ?Certificate
    {
        // QR URLs are pasted whole often enough to be worth handling.
        if (str_contains($reference, '/verify/')) {
            $reference = trim(parse_url($reference, PHP_URL_PATH) ?? '', '/');
            $reference = substr($reference, strrpos($reference, '/') + 1);
        }
        return Certificate::with('studentRecord')
            ->where(function ($query) use ($reference) {
                $query->where('verification_token', $reference)
                    ->orWhere('serial_number', mb_strtoupper($reference))
                    ->orWhere('content_hash', mb_strtolower($reference));
            })
            ->first();
    }
    protected function outcome(string $result, ?Certificate $certificate): array
    {
        return [
            'result'      => $result,
            'certificate' => $certificate,
            'message'     => $this->message($result, $certificate),
        ];
    }
    protected function message(string $result, ?Certificate $certificate): string
    {
        return match ($result) {
            self::AUTHENTIC => 'This document was issued by Partido State University and its contents are unchanged.',
            self::REVOKED   => $certificate?->status === 'superseded'
                ? 'This document has been replaced by a newer issuance. Ask the holder for the current copy.'
                : 'This document was issued by Partido State University but has since been revoked. It is no longer valid.',
            self::TAMPERED  => 'The record for this document does not match its original fingerprint. Do not accept this copy — report it to the Office of the University Registrar.',
            default         => 'No document matches this reference. Check the serial number, or scan the QR code again.',
        };
    }

    protected function log(string $reference, string $method, array $outcome, ?Request $request): void
    {
        VerificationLog::create([
            'certificate_id'      => $outcome['certificate']?->id,
            'submitted_reference' => mb_substr($reference, 0, 255),
            'method'              => $method,
            'result'              => $outcome['result'],
            'document_type'       => $outcome['certificate']?->document_type,
            'ip_address'          => $request?->ip(),
            'user_agent'          => mb_substr((string) $request?->userAgent(), 0, 500),
            'referrer'            => $request?->headers->get('referer'),
            'user_id'             => auth()->id(),
        ]);
    }
}