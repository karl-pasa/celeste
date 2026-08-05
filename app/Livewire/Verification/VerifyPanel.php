<?php

namespace App\Livewire\Verification;

use App\Services\VerificationService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Drives both the typed lookup and the camera scanner on the public portal.
 * The scanner dispatches `qr-scanned` from JavaScript with the decoded text.
 */
class VerifyPanel extends Component
{
    public string $reference = '';
    public string $method = 'serial';
    public ?array $outcome = null;
    public bool $checking = false;

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    protected array $messages = [
        'reference.required' => 'Enter a serial number or scan the QR code on the document.',
        'reference.min'      => 'That reference looks too short to be a certificate serial.',
    ];

    public function verify(VerificationService $verifier): void
    {
        $this->validate();

        $result = $verifier->verify($this->reference, $this->method, request());

        $this->outcome = [
            'result'  => $result['result'],
            'message' => $result['message'],
            'record'  => $result['certificate'] ? [
                'serial'     => $result['certificate']->serial_number,
                'type'       => $result['certificate']->type_label,
                'holder'     => $result['certificate']->studentRecord?->full_name,
                'program'    => $result['certificate']->studentRecord?->program,
                'college'    => $result['certificate']->studentRecord?->college,
                'issued_on'  => $result['certificate']->issued_on?->format('F j, Y'),
                'hash'       => $result['certificate']->shortHash(),
                'reason'     => $result['certificate']->revocation_reason,
                'checks'     => $result['certificate']->verification_count,
            ] : null,
        ];
    }

    #[On('qr-scanned')]
    public function handleScan(string $value, VerificationService $verifier): void
    {
        $this->reference = $value;
        $this->method = 'qr_scan';
        $this->verify($verifier);
    }

    public function reset_check(): void
    {
        $this->reset(['reference', 'outcome']);
        $this->method = 'serial';
    }

    public function render()
    {
        return view('livewire.verification.verify-panel');
    }
}
