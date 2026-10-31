<?php

namespace App\Livewire\Analytics;

use App\Services\AnalyticsService;
use Livewire\Attributes\Url;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    #[Url]
    public int $days = 30;

    public string $resultFilter = '';

    public function setPeriod(int $days, AnalyticsService $analytics): void
    {
        $this->days = in_array($days, [7, 30, 90], true) ? $days : 30;

        // The charts live in wire:ignore blocks, so Livewire does not redraw
        // them. Hand them the new numbers instead.
        $this->dispatch('analytics-refresh', charts: $this->chartData($analytics));
    }

    /**
     * Called by the page every few seconds (wire:poll). Livewire re-renders
     * the numbers and tables itself; the charts sit in wire:ignore blocks,
     * so they are handed the fresh figures here.
     */
    public function refresh(AnalyticsService $analytics): void
    {
        $this->dispatch('analytics-refresh', charts: $this->chartData($analytics));
    }

    protected function chartData(AnalyticsService $analytics): array
    {
        return [
            'series' => $analytics->volumeSeries($this->days),
            'byType' => collect($analytics->byDocumentType($this->days))
                ->map(fn ($t) => ['label' => $t['label'], 'total' => $t['total']])
                ->values()->all(),
        ];
    }

    public function render(AnalyticsService $analytics)
    {
        return view('livewire.analytics.analytics-dashboard', [
            'summary'     => $analytics->summary($this->days),
            'series'      => $analytics->volumeSeries($this->days),
            'byType'      => $analytics->byDocumentType($this->days),
            'byMethod'    => $analytics->byMethod($this->days),
            'flags'       => $analytics->decisionFlags($this->days),
            'mostChecked' => $analytics->mostVerified(),
            'activity'    => $analytics->recentActivity(15),
        ]);
    }
}