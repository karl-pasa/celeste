<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\VerificationLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Verification Analytics Module + Institutional Decision Support.
 *
 * The analytics half reports what happened. The decision support half reads the
 * same data for patterns the Registrar should act on — a document type drawing
 * repeated failed checks, one certificate scanned from unusually many addresses,
 * a revoked credential still circulating.
 */
class AnalyticsService
{
    public function summary(int $days = 30): array
    {
        $since = now()->subDays($days);
        $prior = now()->subDays($days * 2);

        $current  = VerificationLog::where('created_at', '>=', $since)->count();
        $previous = VerificationLog::whereBetween('created_at', [$prior, $since])->count();

        $authentic = VerificationLog::where('created_at', '>=', $since)
            ->where('result', 'authentic')->count();

        return [
            'total_certificates'   => Certificate::count(),
            'active_certificates'  => Certificate::issued()->count(),
            'revoked_certificates' => Certificate::where('status', 'revoked')->count(),
            'verifications'        => $current,
            'verifications_change' => $this->percentChange($previous, $current),
            'authentic'            => $authentic,
            'failed'               => $current - $authentic,
            'success_rate'         => $current > 0 ? round(($authentic / $current) * 100, 1) : 0.0,
            'issued_this_period'   => Certificate::where('created_at', '>=', $since)->count(),
        ];
    }

    /** Daily verification volume split by outcome — feeds the main chart. */
    public function volumeSeries(int $days = 30): array
    {
        $rows = VerificationLog::selectRaw('DATE(created_at) as day, result, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('day', 'result')
            ->get();

        $labels    = [];
        $authentic = [];
        $failed    = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('M j');

            $dayRows = $rows->where('day', $date);
            $authentic[] = (int) $dayRows->where('result', 'authentic')->sum('total');
            $failed[]    = (int) $dayRows->where('result', '!=', 'authentic')->sum('total');
        }

        return compact('labels', 'authentic', 'failed');
    }

    public function byDocumentType(int $days = 30): array
    {
        $labels = Certificate::types();

        $counts = VerificationLog::selectRaw('document_type, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('document_type')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        return collect($labels)
            ->map(fn ($label, $key) => [
                'label' => $label,
                'total' => (int) ($counts[$key] ?? 0),
            ])
            ->values()
            ->all();
    }

    public function byMethod(int $days = 30): array
    {
        return VerificationLog::selectRaw('method, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->all();
    }

    public function issuanceByType(): array
    {
        return Certificate::selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type')
            ->all();
    }

    public function recentActivity(int $limit = 12)
    {
        return VerificationLog::with('certificate.studentRecord')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function mostVerified(int $limit = 5)
    {
        return Certificate::with('studentRecord')
            ->where('verification_count', '>', 0)
            ->orderByDesc('verification_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Institutional Decision Support — each flag names what happened, why it
     * matters, and what the Registrar can do about it.
     *
     * @return array<int, array{severity:string, title:string, detail:string, action:string}>
     */
    public function decisionFlags(int $days = 30): array
    {
        $flags = [];
        $since = now()->subDays($days);

        // 1. Failed verifications running above the tolerance threshold.
        $total  = VerificationLog::where('created_at', '>=', $since)->count();
        $failed = VerificationLog::where('created_at', '>=', $since)
            ->whereIn('result', ['tampered', 'not_found'])->count();

        if ($total >= 20 && ($failed / $total) > config('celeste.analytics.failure_threshold')) {
            $rate = round(($failed / $total) * 100, 1);
            $flags[] = [
                'severity' => 'high',
                'title'    => "Failed verifications at {$rate}%",
                'detail'   => "{$failed} of {$total} checks in the last {$days} days did not resolve to a valid document.",
                'action'   => 'Review the failed attempts log for repeated serials — a cluster usually means forged copies in circulation.',
            ];
        }

        // 2. A document type attracting disproportionate failures.
        $byType = VerificationLog::selectRaw('document_type, COUNT(*) as total')
            ->where('created_at', '>=', $since)
            ->whereIn('result', ['tampered', 'not_found'])
            ->whereNotNull('document_type')
            ->groupBy('document_type')
            ->orderByDesc('total')
            ->first();

        if ($byType && $byType->total >= 5) {
            $label = Certificate::types()[$byType->document_type] ?? $byType->document_type;
            $flags[] = [
                'severity' => 'medium',
                'title'    => "{$label} is the most disputed document",
                'detail'   => "{$byType->total} failed checks were traced to this document type.",
                'action'   => 'Consider adding a second security feature to this template — a dry seal position change or an additional printed control number.',
            ];
        }

        // 3. Revoked credentials still being presented.
        $revokedHits = VerificationLog::where('created_at', '>=', $since)
            ->where('result', 'revoked')->count();

        if ($revokedHits > 0) {
            $flags[] = [
                'severity' => 'high',
                'title'    => 'Revoked documents are still being presented',
                'detail'   => "{$revokedHits} scans resolved to a revoked or superseded certificate.",
                'action'   => 'Notify the holders that their copy is void and issue the replacement on record.',
            ];
        }

        // 4. One certificate checked from an unusual number of addresses.
        $spread = VerificationLog::selectRaw('certificate_id, COUNT(DISTINCT ip_address) as ips')
            ->where('created_at', '>=', $since)
            ->whereNotNull('certificate_id')
            ->groupBy('certificate_id')
            ->having(DB::raw('COUNT(DISTINCT ip_address)'), '>=', config('celeste.analytics.spread_threshold'))
            ->orderByDesc('ips')
            ->first();

        if ($spread) {
            $certificate = Certificate::find($spread->certificate_id);
            $flags[] = [
                'severity' => 'medium',
                'title'    => 'Unusual scan spread on one document',
                'detail'   => "{$certificate?->serial_number} was verified from {$spread->ips} distinct addresses in {$days} days.",
                'action'   => 'Normal for a graduate job-hunting; worth a look if the holder has not authorised that many checks.',
            ];
        }

        // 5. Verification demand rising — capacity signal, not a threat.
        $summary = $this->summary($days);
        if ($summary['verifications_change'] >= 40 && $summary['verifications'] >= 30) {
            $flags[] = [
                'severity' => 'info',
                'title'    => "Verification demand up {$summary['verifications_change']}%",
                'detail'   => 'Employer and school checks have risen sharply against the previous period.',
                'action'   => 'Expect follow-up requests at the counter. Point walk-ins to the public portal to keep the queue down.',
            ];
        }

        if ($flags === []) {
            $flags[] = [
                'severity' => 'ok',
                'title'    => 'Nothing needs your attention',
                'detail'   => "No unusual verification patterns in the last {$days} days.",
                'action'   => 'Checks are resolving normally across all four document types.',
            ];
        }

        return $flags;
    }

    protected function percentChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
