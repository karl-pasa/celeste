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

    public function setPeriod(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90], true) ? $days : 30;
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
