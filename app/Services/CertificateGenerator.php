<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\StudentRecord;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Certificate Generation Module.
 *
 * Issuing a certificate is a single transaction: build the payload snapshot,
 * hash it, persist it, then render the PDF with the QR already embedded.
 * Nothing becomes downloadable until the hash exists, so an unverifiable
 * document can never leave the system.
 */
class CertificateGenerator
{
    public function __construct(
        protected CertificateHashService $hasher,
        protected QrCodeService $qr,
        protected PdfTemplateStamper $stamper,
    ) {}

    public function issue(
        StudentRecord $student,
        string $documentType,
        User $registrar,
        array $overrides = [],
        ?CertificateBatch $batch = null,
    ): Certificate {
        return DB::transaction(function () use ($student, $documentType, $registrar, $overrides, $batch) {
            $issuedOn = isset($overrides['issued_on'])
                ? Carbon::parse($overrides['issued_on'])
                : now();

            $serial = $this->nextSerial($documentType, $issuedOn);
            $payload = array_merge(
                $this->buildPayload($student, $documentType, $serial, $issuedOn),
                Str::of('')->isEmpty() ? [] : [],
                $overrides['payload'] ?? []
            );

            $certificate = Certificate::create([
                'serial_number'      => $serial,
                'verification_token' => $this->uniqueToken(),
                'document_type'      => $documentType,
                'student_record_id'  => $student->id,
                'issued_by'          => $registrar->id,
                'batch_id'           => $batch?->id,
                'payload'            => $payload,
                'content_hash'       => $this->hasher->hash($payload),
                'status'             => 'issued',
                'issued_on'          => $issuedOn->toDateString(),
            ]);

            $this->qr->store($certificate);
            $this->renderPdf($certificate);

            AuditLog::record('certificate.generated', $certificate, [
                'document_type' => $documentType,
                'student'       => $student->student_number,
                'batch'         => $batch?->reference,
            ]);

            return $certificate->fresh();
        });
    }

    /**
     * Batch generation. Each certificate is issued independently so one bad
     * record cannot roll back an entire graduating class.
     */
    public function issueBatch(
        array $studentIds,
        string $documentType,
        User $registrar,
        string $label,
    ): CertificateBatch {
        $batch = CertificateBatch::create([
            'reference'     => 'BATCH-' . now()->format('Y') . '-' . str_pad((string) (CertificateBatch::whereYear('created_at', now()->year)->count() + 1), 4, '0', STR_PAD_LEFT),
            'label'         => $label,
            'document_type' => $documentType,
            'total'         => count($studentIds),
            'status'        => 'processing',
            'created_by'    => $registrar->id,
        ]);

        $errors = [];

        foreach ($studentIds as $id) {
            $student = StudentRecord::find($id);

            if (! $student) {
                $errors[] = ['student_id' => $id, 'message' => 'Student record not found.'];
                $batch->increment('failed');
                continue;
            }

            try {
                $this->issue($student, $documentType, $registrar, [], $batch);
                $batch->increment('generated');
            } catch (\Throwable $e) {
                $errors[] = [
                    'student_id' => $id,
                    'student'    => $student->student_number,
                    'message'    => $e->getMessage(),
                ];
                $batch->increment('failed');
            }
        }

        $batch->update([
            'status'       => $batch->failed === $batch->total ? 'failed' : 'completed',
            'errors'       => $errors ?: null,
            'completed_at' => now(),
        ]);

        AuditLog::record('batch.generated', $batch, [
            'generated' => $batch->generated,
            'failed'    => $batch->failed,
        ]);

        return $batch->fresh();
    }

    /**
     * The canonical snapshot of everything printed on the document.
     * Once hashed, changing any value here invalidates the certificate.
     */
    public function buildPayload(
        StudentRecord $student,
        string $documentType,
        string $serial,
        Carbon $issuedOn,
    ): array {
        $base = [
            'serial_number'  => $serial,
            'document_type'  => $documentType,
            'student_number' => $student->student_number,
            'full_name'      => $student->formal_name,
            'college'        => $student->college,
            'program'        => $student->program,
            'issued_on'      => $issuedOn->toDateString(),
            'institution'    => config('celeste.institution.name'),
            'campus'         => config('celeste.institution.campus'),
        ];

        return match ($documentType) {
            Certificate::TYPE_DIPLOMA => $base + [
                'date_graduated' => optional($student->date_graduated)->toDateString(),
                'latin_honor'    => $student->latin_honor,
                'major'          => $student->major,
            ],
            Certificate::TYPE_DISMISSAL => $base + [
                'date_admitted'  => optional($student->date_admitted)->toDateString(),
                'last_semester'  => $student->semester,
                'academic_year'  => $student->academic_year,
                'purpose'        => 'Transfer to another institution',
                'address'        => $student->address,      
                'year_level'     => $student->year_level,   
            ],
            Certificate::TYPE_ENROLMENT => $base + [
                'year_level'    => $student->year_level,
                'semester'      => $student->semester,
                'academic_year' => $student->academic_year,
                'status'        => $student->status,
            ],
            Certificate::TYPE_TOR => $base + [
                'grades'        => $student->grades ?? [],
                'total_units'   => $student->totalUnits(),
                'gwa'           => $student->general_weighted_average,
                'date_admitted' => optional($student->date_admitted)->toDateString(),
                'date_graduated'=> optional($student->date_graduated)->toDateString(),
            ],
            default => $base,
        };
    }

    /**
     * Render the PDF with the QR embedded, then fingerprint the file itself.
     */
    public function renderPdf(Certificate $certificate): string
    {
        $certificate->loadMissing('studentRecord', 'issuer');

        // If the Registrar has supplied their own approved PDF form for this
        // document type, stamp onto that instead of rendering a Blade layout.
        if ($this->stamper->hasTemplate($certificate->document_type)) {
            return $this->storeRendered($certificate, $this->stamper->render($certificate));
        }

        return $this->storeRendered($certificate, $this->buildDompdf($certificate)->output());
    }

    /**
     * Build the DomPDF instance for a certificate using its Blade layout.
     */
    protected function buildDompdf(Certificate $certificate)
    {
        $certificate->loadMissing('studentRecord', 'issuer');

        $view = match ($certificate->document_type) {
            Certificate::TYPE_DIPLOMA   => 'pdf.diploma',
            Certificate::TYPE_DISMISSAL => 'pdf.honorable-dismissal',
            Certificate::TYPE_ENROLMENT => 'pdf.certificate-of-enrolment',
            Certificate::TYPE_TOR       => 'pdf.transcript-of-records',
        };

        $paper = $certificate->document_type === Certificate::TYPE_DIPLOMA
            ? ['a4', 'landscape']
            : ['a4', 'portrait'];

        return Pdf::loadView($view, [
            'certificate' => $certificate,
            'student'     => $certificate->studentRecord,
            'payload'     => $certificate->payload,
            'qr'          => $this->qr->dataUri($certificate),
            'verifyUrl'   => $this->qr->payloadUrl($certificate),
        ])->setPaper(...$paper);

        return $this->storeRendered($certificate, $pdf->output());
    }

    /**
     * Render and return the PDF bytes, whether or not they can be saved.
     *
     * Serverless hosts mount the project read-only, so persisting is a
     * best-effort step rather than a precondition for serving the document.
     */
    public function renderBinary(Certificate $certificate): string
    {
        $certificate->loadMissing('studentRecord', 'issuer');

        if ($this->stamper->hasTemplate($certificate->document_type)) {
            $binary = $this->stamper->render($certificate);
            $this->storeRendered($certificate, $binary);

            return $binary;
        }

        $binary = $this->buildDompdf($certificate)->output();
        $this->storeRendered($certificate, $binary);

        return $binary;
    }

    /**
     * Persist the rendered bytes and fingerprint the file itself, whichever
     * renderer produced them.
     *
     * A write failure is survivable. The PDF is a rendering of the hashed
     * payload, not the record itself, so it can always be rebuilt — and on a
     * read-only filesystem it must be. Losing the response over a cache miss
     * would be the wrong trade.
     */
    protected function storeRendered(Certificate $certificate, string $binary): string
    {
        $path = "certificates/files/{$certificate->verification_token}.pdf";

        $attributes = [];

        // Fingerprint the file once, at first issuance. Later re-renders after
        // a template change would otherwise silently replace the fingerprint
        // that was recorded when the document was issued.
        if (! $certificate->file_hash) {
            $attributes['file_hash'] = $this->hasher->hashFile($binary);
        }

        try {
            Storage::disk('local')->put($path, $binary);
            $attributes['file_path'] = $path;
        } catch (\Throwable $e) {
            report($e);
            $path = '';
        }

        if ($attributes !== []) {
            $certificate->forceFill($attributes)->save();
        }

        return $path;
    }

    public function nextSerial(string $documentType, Carbon $issuedOn): string
    {
        $code = Certificate::typeCode($documentType);
        $year = $issuedOn->format('Y');

        $sequence = Certificate::where('document_type', $documentType)
            ->whereYear('issued_on', $year)
            ->count() + 1;

        do {
            $serial = sprintf('PSU-%s-%s-%06d', $code, $year, $sequence);
            $sequence++;
        } while (Certificate::where('serial_number', $serial)->exists());

        return $serial;
    }

    protected function uniqueToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (Certificate::where('verification_token', $token)->exists());

        return $token;
    }

    /**
     * Reissue supersedes the old certificate rather than editing it —
     * the original hash stays intact for anyone holding a printed copy.
     */
    public function reissue(Certificate $original, User $registrar, string $reason): Certificate
    {
        $replacement = $this->issue(
            $original->studentRecord,
            $original->document_type,
            $registrar,
            ['payload' => ['supersedes' => $original->serial_number]],
        );

        $replacement->update(['supersedes_id' => $original->id]);
        $original->update([
            'status'            => 'superseded',
            'revocation_reason' => $reason,
            'revoked_by'        => $registrar->id,
            'revoked_at'        => now(),
        ]);

        AuditLog::record('certificate.reissued', $replacement, [
            'supersedes' => $original->serial_number,
            'reason'     => $reason,
        ]);

        return $replacement;
    }
}
