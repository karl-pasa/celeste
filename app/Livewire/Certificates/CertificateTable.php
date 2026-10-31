<?php

namespace App\Livewire\Certificates;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CertificateBatch;
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

    // Class filters. Each one narrows the next, as on Batch generation, and
    // they match the student's record (college, program, year level, section).
    #[Url]
    public string $college = '';

    #[Url]
    public string $program = '';

    #[Url]
    public string $yearLevel = '';

    #[Url]
    public string $section = '';

    // Date the document was issued: from / to, both optional and inclusive.
    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    /** Show only the documents of one batch (?batch=ID). */
    #[Url]
    public string $batch = '';

    public string $sort = 'created_at';
    public string $direction = 'desc';

    // Revoke / reissue modal state
    public ?int $actingOn = null;
    public string $action = '';
    public string $reason = '';

    public function updating($field): void
    {
        if (in_array($field, ['search', 'type', 'status', 'batch', 'college', 'program',
                              'yearLevel', 'section', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    // Choosing a different college clears what was chosen beneath it, so a
    // BSIT filter is never left standing under the College of Education.
    public function updatedCollege(): void
    {
        $this->program = '';
        $this->yearLevel = '';
        $this->section = '';
    }

    public function updatedProgram(): void
    {
        $this->yearLevel = '';
        $this->section = '';
    }

    public function updatedYearLevel(): void
    {
        $this->section = '';
    }

    public function sortBy(string $column): void
    {
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'status', 'batch', 'college', 'program',
            'yearLevel', 'section', 'dateFrom', 'dateTo']);
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
        $certificates = Certificate::with(['studentRecord', 'issuer', 'batch'])
            ->when($this->batch !== '', fn ($q) => $q->where('batch_id', (int) $this->batch))
            ->when($this->search !== '', fn ($q) => $q->where(function ($w) {
                // Serial, name and number as before, plus the batch's name or reference.
                $w->search($this->search)
                  ->orWhereHas('batch', fn ($b) => $b
                      ->where('reference', 'ilike', "%{$this->search}%")
                      ->orWhere('label', 'ilike', "%{$this->search}%"));
            }))
            ->ofType($this->type ?: null)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('issued_on', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('issued_on', '<=', $this->dateTo))
            ->when($this->college !== '' || $this->program !== '' || $this->yearLevel !== '' || $this->section !== '',
                fn ($q) => $q->whereHas('studentRecord', function ($r) {
                    $r->when($this->college !== '',   fn ($x) => $x->where('college', $this->college))
                      ->when($this->program !== '',   fn ($x) => $x->where('program', $this->program))
                      ->when($this->yearLevel !== '', fn ($x) => $x->where('year_level', $this->yearLevel))
                      ->when($this->section !== '',   fn ($x) => $x->where('section', $this->section));
                }))
            ->orderBy($this->sort, $this->direction)
            ->paginate(12);

        // Flag rows whose stored payload no longer matches its fingerprint.
        $integrity = $certificates->mapWithKeys(
            fn ($c) => [$c->id => $hasher->matches($c)]
        );

        return view('livewire.certificates.certificate-table', [
            'certificates' => $certificates,
            'integrity'    => $integrity,
            'activeBatch'  => $this->batch !== '' ? CertificateBatch::find((int) $this->batch) : null,
            'types'        => Certificate::types(),
            'colleges'     => config('celeste.colleges', []),
            'programs'     => $this->college !== ''
                ? (config('celeste.programs', [])[$this->college] ?? [])
                : [],
            'yearLevels'   => config('celeste.academics.year_levels', []),
            'sections'     => config('celeste.academics.sections', []),
        ]);
    }
}