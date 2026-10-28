<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateGenerator;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

/**
 * Public Verification Portal — open to employers, other schools, and anyone
 * holding a printed document. No account required.
 */
class PublicVerificationController extends Controller
{
    public function __construct(protected VerificationService $verifier) {}

    public function index(): View
    {
        return view('public.portal');
    }

    public function scanner(): View
    {
        return view('public.scanner');
    }

    public function verify(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'method'    => ['nullable', 'in:serial,hash,qr_scan'],
        ]);

        $method = $data['method'] ?? (strlen($data['reference']) === 64 ? 'hash' : 'serial');

        return view('public.result', [
            'outcome'   => $this->verifier->verify($data['reference'], $method, $request),
            'reference' => $data['reference'],
        ]);
    }

    /**
     * Landing point for a scanned QR code.
     */
    public function token(string $token, Request $request): View
    {
        return view('public.result', [
            'outcome'   => $this->verifier->verify($token, 'qr_scan', $request),
            'reference' => $token,
        ]);
    }

    /**
     * The issued document itself, shown inside the verification result.
     *
     * Only a document that is currently valid is served -- a revoked or
     * superseded one is never displayed as though it were good. Reading the
     * stored PDF is preferred; where the file is missing (the server's disk
     * is not permanent on every host) it is rebuilt from the certificate's
     * own saved payload, so it still shows exactly what was issued.
     *
     * Viewing is not a verification, so nothing is written to the log here.
     */
    public function preview(string $token, CertificateGenerator $generator): Response
    {
        $certificate = Certificate::where('verification_token', $token)
            ->where('status', 'issued')
            ->firstOrFail();

        $disk = Storage::disk('local');

        $binary = ($certificate->file_path && $disk->exists($certificate->file_path))
            ? $disk->get($certificate->file_path)
            : $generator->renderBinary($certificate);

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $certificate->serial_number . '.pdf"',
            'Cache-Control'       => 'private, no-store',
            'X-Robots-Tag'        => 'noindex, nofollow',
            'X-Frame-Options'     => 'SAMEORIGIN',
        ]);
    }
}