<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Verification Module.
 *
 * Resolves a scanned or typed reference to a certificate, recomputes its hash,
 * and returns one of four outcomes. Every attempt is logged — including the
 * failures, which are the interesting ones for the analytics module.
 *
 * ---------------------------------------------------------------------------
 * Why the verification counters are written through the query builder
 * ---------------------------------------------------------------------------
 * Recording a successful verification previously used
 * forceFill([...])->save() on the model. Eloquent's save() writes every
 * attribute it considers dirty, not only the ones just filled, and a JSON-cast
 * attribute such as payload is marked dirty merely by having been read and
 * re-encoded. The re-encoded JSON is not byte-identical to what was stored,
 * because the database returns object keys in its own order rather than the
 * order they were written in.
 *
 * The payload was therefore rewritten while content_hash was left untouched,
 * so the fingerprint no longer described the bytes it was computed over. The
 * effect was that a certificate verified correctly exactly once and reported
 * as TAMPERED on every subsequent scan.
 *
 * Writing the two counter columns directly leaves payload alone. The rule this
 * enforces is that payload and content_hash are written together at issuance
 * and never again.
 */
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

    /**
     * Record that a certificate was successfully verified.
     *
     * Both columns are updated in one statement through the query builder, so
     * that no other column — in particular payload — is touched. The counter
     * is incremented in SQL rather than read and rewritten, which also keeps
     * the count correct when two verifications arrive at the same moment.
     */
    protected function recordVerification(Certificate $certificate): void
    {
        DB::table('certificates')
            ->where('id', $certificate->id)
            ->update([
                'verification_count' => DB::raw('COALESCE(verification_count, 0) + 1'),
                'last_verified_at'   => now(),
            ]);

        // Keep the in-memory model consistent with the row without marking
        // anything dirty, so a later save elsewhere cannot write these back.
        $certificate->forceFill([
            'verification_count' => ($certificate->verification_count ?? 0) + 1,
            'last_verified_at'   => now(),
        ])->syncOriginal();
    }

    /**
     * A verifier may present a QR token, a printed serial, or a raw hash.
     *
     * The three alternatives are grouped so that adding any further condition
     * to this query later cannot accidentally fall outside the OR and match
     * every certificate.
     */
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
