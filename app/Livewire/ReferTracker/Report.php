<?php

declare(strict_types=1);

namespace App\Livewire\ReferTracker;

use App\Enums\EarningType;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Refer;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Report extends Component
{
    #[Locked]
    public Refer $refer;

    #[Locked]
    public Campaign $campaign;

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

    public function mount(Refer $refer): void
    {
        $this->refer = $refer;
        $this->campaign = $refer->campaign;
    }

    public function render(): View
    {
        return view('livewire.refer-tracker.report', $this->getAnalyticsData())
            ->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        return app(GeneralSettings::class)->site_name.' | Referrer Report';
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
     * Masks a UPI ID for display.
     */
    public function maskUpi(string $upi): string
    {
        if (! str_contains($upi, '@')) {
            return $this->maskValue($upi);
        }
        $parts = explode('@', $upi, 2);
        $username = $parts[0];
        $domain = $parts[1];

        if (mb_strlen($username) <= 2) {
            return '****@'.$domain;
        }

        return mb_substr($username, 0, 1).'****'.mb_substr($username, -1).'@'.$domain;
    }

    /**
     * Masks a generic string value for display.
     */
    public function maskValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        if (mb_strlen($value) <= 4) {
            return '****';
        }

        return mb_substr($value, 0, 2).'****'.mb_substr($value, -2);
    }

    /**
     * Triggers a CSV download of the current report.
     */
    public function export(): StreamedResponse
    {
        // Get clicks filtered by date range and search term.
        $allClicks = $this->getFilteredClicks();

        // Only include clicks that have at least one conversion in the activity log.
        $clicksWithConversions = $allClicks->filter(fn (Click $click) => $click->conversions->isNotEmpty());

        $campaign = $this->campaign;
        $self = $this;

        $fileName = 'referral_report_'.$this->refer->id.'_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Click Date', 'User UPI'];
        if ($campaign->is_extra_input_1_active) {
            $columns[] = $campaign->extra_input_1_label;
        }
        if ($campaign->is_extra_input_2_active) {
            $columns[] = $campaign->extra_input_2_label;
        }
        if ($campaign->is_extra_input_3_active) {
            $columns[] = $campaign->extra_input_3_label;
        }
        $columns = array_merge($columns, ['Event Label', 'Event Status', 'Completion Date', 'Referral Earning', 'Payment Status']);

        $callback = function () use ($clicksWithConversions, $campaign, $columns, $self): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $allCampaignEvents = $campaign->events->sortBy('sort_order');

            foreach ($clicksWithConversions as $click) {
                foreach ($allCampaignEvents as $event) {
                    $conversion = $click->conversionsByEvent->get($event->id);

                    $rowData = [
                        'Click Date' => $click->created_at->format('Y-m-d H:i:s'),
                        'User UPI' => $self->maskUpi($click->upi),
                    ];

                    if ($campaign->is_extra_input_1_active) {
                        $rowData[$campaign->extra_input_1_label] = $self->maskValue($click->extra_input_1);
                    }
                    if ($campaign->is_extra_input_2_active) {
                        $rowData[$campaign->extra_input_2_label] = $self->maskValue($click->extra_input_2);
                    }
                    if ($campaign->is_extra_input_3_active) {
                        $rowData[$campaign->extra_input_3_label] = $self->maskValue($click->extra_input_3);
                    }

                    $rowData['Event Label'] = $event->label;
                    $rowData['Event Status'] = $conversion ? 'Completed' : 'Pending';
                    $rowData['Completion Date'] = $conversion ? $conversion->created_at->format('Y-m-d H:i:s') : 'N/A';

                    // Use the centralized calculator for correct earnings
                    $rowData['Referral Earning'] = $conversion ? $conversion->calculated_amounts['refer'] : '0';

                    $paymentStatus = 'N/A';
                    if ($conversion && $event->is_instant_pay_refer && $event->refer_amount > 0) {
                        $earning = $conversion->earnings
                            ->where('type', EarningType::REFER)
                            ->first();
                        $paymentStatus = $earning ? ucfirst((string) $earning->status) : 'Pending';
                    }
                    $rowData['Payment Status'] = $paymentStatus;

                    fputcsv($file, $rowData);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Prepares and computes all data required for the report view.
     */
    private function getAnalyticsData(): array
    {
        // 1. Get clicks filtered by date range and search term.
        $allClicks = $this->getFilteredClicks();
        // Only include clicks that have at least one conversion in the activity log.
        $clicksWithConversions = $allClicks->filter(fn (Click $click) => $click->conversions->isNotEmpty());
        $allCampaignEvents = $this->campaign->events->sortBy('sort_order');
        $allConversions = $clicksWithConversions->flatMap->conversions;

        $totalEarnings = $allConversions->sum(fn ($conversion) => $conversion->calculated_amounts['refer']);

        return [
            'hasResults' => $clicksWithConversions->isNotEmpty(),
            // Data for header and quick stats
            'totalClicks' => $allClicks->count(),
            'totalEarnings' => $totalEarnings,
            // Data for the detailed activity log
            'clicksByDate' => $clicksWithConversions->groupBy(fn (Click $click) => $click->created_at->format('Y-m-d')),
            'allCampaignEvents' => $allCampaignEvents,
        ];
    }

    /**
     * Fetches clicks from the database based on current filters.
     */
    private function getFilteredClicks(): Collection
    {
        $query = Click::query()
            ->where('refer_id', $this->refer->id)
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
