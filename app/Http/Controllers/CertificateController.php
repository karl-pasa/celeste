<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Services\CertificateGenerator;
use App\Services\CertificateHashService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateGenerator $generator,
        protected CertificateHashService $hasher,
        protected QrCodeService $qr,
    ) {}

    public function index(): View
    {
        return view('registrar.certificates');
    }

    public function create(): View
    {
        return view('registrar.generate');
    }

    public function batch(): View
    {
        return view('registrar.batch');
    }

    public function students(): View
    {
        return view('registrar.students');
    }

    public function show(Certificate $certificate): View
    {
        $certificate->load('studentRecord', 'issuer', 'batch', 'verificationLogs');

        return view('registrar.certificate-show', [
            'certificate'  => $certificate,
            'hashIntact'   => $this->hasher->matches($certificate),
            'qr'           => $this->qr->dataUri($certificate, 220),
        ]);
    }

    public function download(Certificate $certificate, Request $request): Response
    {
        $this->authorizeAccess($certificate, $request);

        $binary = $this->fileFor($certificate);

        AuditLog::record('certificate.downloaded', $certificate);

        $filename = str($certificate->type_label)->slug() . '-' . $certificate->serial_number . '.pdf';

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function print(Certificate $certificate, Request $request): Response
    {
        $this->authorizeAccess($certificate, $request);

        AuditLog::record('certificate.printed', $certificate);

        return response($this->fileFor($certificate), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $certificate->serial_number . '.pdf"',
        ]);
    }

    public function qr(Certificate $certificate, Request $request): Response
    {
        $this->authorizeAccess($certificate, $request);

        return response($this->qr->render($certificate, 600), 200, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Regenerate on demand if the stored PDF is missing — the hash is the
     * source of truth, the file is just a rendering of it.
     */
    protected function fileFor(Certificate $certificate): string
    {
        // Serve the stored copy when there is one.
        try {
            if ($certificate->file_path && Storage::disk('local')->exists($certificate->file_path)) {
                $binary = Storage::disk('local')->get($certificate->file_path);

                if ($binary !== null && $binary !== '') {
                    return $binary;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        // Otherwise rebuild it from the hashed payload. On a read-only host
        // this is the normal path, not the exception -- the stored file is a
        // cache, and the record is the source of truth.
        return $this->generator->renderBinary($certificate);
    }

    protected function authorizeAccess(Certificate $certificate, Request $request): void
    {
        $user = $request->user();

        if ($user->isRegistrar()) {
            return;
        }

        abort_unless(
            $certificate->studentRecord?->student_number === $user->student_number,
            403,
            'This document belongs to another student.'
        );

        abort_if(
            $certificate->status === 'revoked',
            403,
            'This document has been revoked and can no longer be downloaded.'
        );
    }
}
