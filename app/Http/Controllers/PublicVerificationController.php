<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use Illuminate\Http\Request;
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
}
