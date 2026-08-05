<?php

namespace App\Livewire\Certificates;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Services\CertificateGenerator;
use App\Services\CertificateHashService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Certificate Management Module.
 */
class CertificateTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $year = '';

    public string $sort = 'created_at';
    public string $direction = 'desc';

    // Revoke / reissue modal state
    public ?int $actingOn = null;
    public string $action = '';
    public string $reason = '';

    public function updating($field): void
    {
        if (in_array($field, ['search', 'type', 'status', 'year'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'status', 'year']);
        $this->resetPage();
    }

    public function confirm(int $id, string $action): void
    {
        $this->actingOn = $id;
        $this->action = $action;
        $this->reason = '';
    }

    public function cancel(): void
    {
        $this->reset(['actingOn', 'action', 'reason']);
    }

    public function revoke(): void
    {
        $this->validate(['reason' => ['required', 'string', 'min:8', 'max:300']], [
            'reason.required' => 'Give a reason — it is shown to anyone who verifies this document.',
            'reason.min'      => 'Write a little more so the record is meaningful later.',
        ]);

        $certificate = Certificate::findOrFail($this->actingOn);

        $certificate->update([
            'status'            => 'revoked',
            'revocation_reason' => $this->reason,
            'revoked_by'        => auth()->id(),
            'revoked_at'        => now(),
        ]);

        AuditLog::record('certificate.revoked', $certificate, ['reason' => $this->reason]);

        $this->cancel();
        session()->flash('status', "{$certificate->serial_number} is now revoked. Verifications will show it as void.");
    }

    public function reissue(CertificateGenerator $generator): void
    {
        $this->validate(['reason' => ['required', 'string', 'min:8', 'max:300']]);

        $original = Certificate::with('studentRecord')->findOrFail($this->actingOn);
        $replacement = $generator->reissue($original, auth()->user(), $this->reason);

        $this->cancel();
        session()->flash('status', "Reissued as {$replacement->serial_number}. The previous copy is now superseded.");
    }

    public function getActingCertificateProperty(): ?Certificate
    {
        return $this->actingOn ? Certificate::with('studentRecord')->find($this->actingOn) : null;
    }

    public function render(CertificateHashService $hasher)
    {
        $certificates = Certificate::with(['studentRecord', 'issuer'])
            ->search($this->search)
            ->ofType($this->type ?: null)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->year, fn ($q) => $q->whereYear('issued_on', $this->year))
            ->orderBy($this->sort, $this->direction)
            ->paginate(12);

        // Flag rows whose stored payload no longer matches its fingerprint.
        $integrity = $certificates->mapWithKeys(
            fn ($c) => [$c->id => $hasher->matches($c)]
        );

        return view('livewire.certificates.certificate-table', [
            'certificates' => $certificates,
            'integrity'    => $integrity,
            'types'        => Certificate::types(),
            'years'        => Certificate::selectRaw('EXTRACT(YEAR FROM issued_on) as y')
                ->distinct()->orderByDesc('y')->pluck('y'),
        ]);
    }
}
