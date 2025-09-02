<?php

declare(strict_types=1);

namespace App\Livewire\CampaignTracker;

use App\Models\Campaign;
use App\Models\Click;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Report extends Component
{
    #[Locked]
    public Campaign $campaign;

    #[Locked]
    public string $upi;

    /**
     * Search term for filtering events.
     */
    public string $search = '';

    /**
     * Pre-defined date range selection.
     * Options: all, today, yesterday, last7, last30, thisMonth, lastMonth, custom.
     */
    public string $dateRange = 'all';

    /**
     * Custom start date for filtering.
     */
    public ?string $startDate = null;

    /**
     * Custom end date for filtering.
     */
    public ?string $endDate = null;

    public function mount(Campaign $campaign, string $upi): void
    {
        $this->campaign = $campaign;
        $this->upi = $upi;
    }

    public function render(): View
    {
        return view('livewire.campaign-tracker.report', $this->getAnalyticsData())
            ->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        return app(GeneralSettings::class)->site_name.' | Lead Report';
    }

    /**
     * Update the date range and set start/end dates accordingly.
     */
    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;

        match ($range) {
            'today' => $this->setDates(now(), now()),
            'yesterday' => $this->setDates(now()->subDay(), now()->subDay()),
            'last7' => $this->setDates(now()->subDays(6), now()),
            'last30' => $this->setDates(now()->subDays(29), now()),
            'thisMonth' => $this->setDates(now()->startOfMonth(), now()->endOfMonth()),
            'lastMonth' => $this->setDates(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()),
            default => $this->clearDates()
        };
    }

    /**
     * Prepares and computes all data required for the report view.
     */
    private function getAnalyticsData(): array
    {
        // 1. Get clicks filtered by date range and search term.
        $allClicks = $this->getFilteredClicks();
        // Only include clicks that have at least one conversion.
        $clicks = $allClicks->filter(fn (Click $click) => $click->conversions->isNotEmpty());
        $allCampaignEvents = $this->campaign->events->sortBy('sort_order');
        $allConversions = $clicks->flatMap->conversions;

        $totalEarnings = $allConversions->sum(fn ($conversion) => $conversion->calculated_amounts['user']);

        return [
            'hasResults' => $clicks->isNotEmpty(),
            // Data for header and quick stats
            'totalClicks' => $allClicks->count(),
            'totalEarnings' => $totalEarnings,
            // Data for the detailed activity log
            'clicksByDate' => $clicks->groupBy(fn (Click $click) => $click->created_at->format('Y-m-d')),
            'allCampaignEvents' => $allCampaignEvents,
        ];
    }

    /**
     * Fetches clicks from the database based on current filters.
     */
    private function getFilteredClicks(): Collection
    {
        $query = Click::query()
            ->where('campaign_id', $this->campaign->id)
            ->where('upi', $this->upi)
            // Eager load relationships to prevent N+1 query issues.
            ->with([
                'conversions.event',
                'conversions.earnings.payout',
                'conversions.click.refer',
            ])
            ->latest();

        // Apply date filter if a range is set.
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        // Apply search filter using a more efficient `whereHas` clause.
        // This filters clicks that have at least one conversion with a matching event label.
        if ($this->search !== '') {
            $query->whereHas('conversions.event', function (Builder $q): void {
                // Use whereRaw for a case-insensitive search.
                $q->whereRaw('LOWER(label) LIKE ?', ['%'.mb_strtolower($this->search).'%']);
            });
        }

        $clicks = $query->get();

        // **Performance Optimization for the View**
        // Create a lookup map of conversions keyed by event_id on each click model.
        // This makes finding a specific conversion for an event in the Blade view
        // an O(1) operation instead of an O(N) collection search.
        $clicks->each(function (Click $click): void {
            $click->conversionsByEvent = $click->conversions->keyBy('event_id');
        });

        return $clicks;
    }

    private function setDates(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }

    private function clearDates(): void
    {
        $this->startDate = null;
        $this->endDate = null;
    }
}
