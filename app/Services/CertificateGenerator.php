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

    /**
     * Issue one certificate.
     *
     * $overrides accepts two keys:
     *   'issued_on' — the date of issue, otherwise today
     *   'payload'   — values supplied by the caller, merged over those read
     *                 from the student record BEFORE hashing, so anything
     *                 printed is covered by the fingerprint
     *
     * There was previously an unused $extra parameter sitting ahead of
     * $overrides. Because it was never read, every caller passing values in
     * the fourth position had them silently discarded — including reissue(),
     * whose 'supersedes' marker never reached a payload. Removing it makes
     * $overrides the fourth argument, which is the position callers were
     * already using.
     */
    public function issue(
         StudentRecord $student,
        string $documentType,
        User $registrar,
        array $overrides = [],
        ?CertificateBatch $batch = null,
    ): Certificate {
        // A student holds one active document of each type. Asking for a
        // second returns the first rather than minting a new serial, a new
        // token and a new QR for a document that already exists — which is
        // what produced duplicate certificates for the same student.
        //
        // When the details have genuinely changed, reissue() is the path:
        // it supersedes the original and leaves any printed copy resolving
        // to an explanation rather than silently competing with a twin.
        if (! ($overrides['force'] ?? false)) {
            $existing = Certificate::where('student_record_id', $student->id)
                ->where('document_type', $documentType)
                ->where('status', 'issued')
                ->latest('id')
                ->first();

            if ($existing) {
                // Re-render so the PDF reflects the current template, without
                // touching the payload or the fingerprint.
                $this->renderPdf($existing);

                return $existing;
            }
        }
        return DB::transaction(function () use ($student, $documentType, $registrar, $overrides, $batch) {
            $issuedOn = isset($overrides['issued_on'])
                ? Carbon::parse($overrides['issued_on'])
                : now();

            $serial = $this->nextSerial($documentType, $issuedOn);

            // Caller-supplied values win over those read from the record, and
            // the merge happens before the hash below. A value printed on the
            // document but absent here would sit outside the fingerprint and
            // could be altered afterwards without verification noticing.
            $payload = array_merge(
                $this->buildPayload($student, $documentType, $serial, $issuedOn),
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
                // The batch is the fifth argument. It was previously passed
                // fifth while the signature expected an array there, which
                // raised a TypeError on the first student of every batch.
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
                // Personal information
                'address'     => $student->address,
                'gender'      => $student->gender,
                'nationality' => $student->nationality,
                'birth_date'  => optional($student->birth_date)->toDateString(),
                'birthplace'  => $student->birthplace,
                'major'       => $student->major,

                // Admission data — both blocks are carried, and the template
                // shows whichever admission_type selects. Storing both means a
                // record corrected from "new" to "transferee" later still has
                // the figures it needs.
                'admission_type'         => $student->admission_type ?? 'new',
                'adm_new_school'         => $student->adm_new_school,
                'adm_new_address'        => $student->adm_new_address,
                'adm_new_course'         => $student->adm_new_course,
                'adm_new_year_graduated' => $student->adm_new_year_graduated,
                'adm_tr_school'          => $student->adm_tr_school,
                'adm_tr_address'         => $student->adm_tr_address,
                'adm_tr_course'          => $student->adm_tr_course,
                'adm_tr_year_graduated'  => $student->adm_tr_year_graduated,
                'adm_tr_credential'      => $student->adm_tr_credential,
                'date_admitted'          => optional($student->date_admitted)->toDateString(),

                // Graduation data
                'date_conferred'        => optional($student->date_conferred)->toDateString(),
                'board_resolution_no'   => $student->board_resolution_no,
                'board_resolution_date' => optional($student->board_resolution_date)->toDateString(),
                'awards'                => $student->awards ?: $student->latin_honor,
                'date_graduated'        => optional($student->date_graduated)->toDateString(),

                // Other printed fields
                'nstp_serial_no'               => $student->nstp_serial_no,
                'program_accreditation'        => $student->program_accreditation,
                'granted_transfer_credentials' => $student->granted_transfer_credentials,
                'remarks'                      => $student->remarks,

                // Subjects
                'grades'      => $student->grades ?? [],
                'total_units' => $student->totalUnits(),
                'gwa'         => $student->general_weighted_average,
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

        $paper = match ($certificate->document_type) {
            Certificate::TYPE_DIPLOMA => ['a4', 'landscape'],

            // Long bond, 8.5 × 13 inches, which is what the Registrar prints
            // transcripts on. Expressed in points because Dompdf has no name
            // for this size.
            //
            // This previously fell through to A4. A4 is 297mm tall against
            // long bond's 330mm, so a layout laid out for the taller sheet
            // lost 33mm and pushed its footer onto a third page — the
            // stylesheet said one size while the renderer used another.
            Certificate::TYPE_TOR => [[0, 0, 612, 936], 'portrait'],

            default => ['a4', 'portrait'],
        };

        return Pdf::loadView($view, [
            'certificate' => $certificate,
            'student'     => $certificate->studentRecord,
            'payload'     => $certificate->payload,

            // A data URI, which Dompdf renders inline without touching the
            // filesystem — so no temporary file, no cleanup, and no chroot
            // restriction to fall foul of.
            'qr'          => $this->qr->dataUri($certificate),
            'verifyUrl'   => $this->qr->payloadUrl($certificate),
        ])->setPaper(...$paper);
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
