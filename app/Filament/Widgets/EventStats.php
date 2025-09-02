<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Models\Event;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

final class EventStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    #[Override]
    protected function getCards(): array
    {
        $createdAtFilter = $this->pageFilters['created_at'] ?? null;
        if (! $createdAtFilter) {
            return []; // Don't render if no date is set
        }

        $createdAtFilterParts = explode(' - ', $createdAtFilter);
        $startDate = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[0])->startOfDay();
        $endDate = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[1])->endOfDay();

        if (empty($this->pageFilters['campaign'])) {
            return [];
        }

        $query = Event::query()
            ->with('campaign.clicks') // Eager load clicks for CR calculation
            ->where('campaign_id', $this->pageFilters['campaign'])
            ->orderBy('sort_order')
            ->with(['conversions' => function (HasMany $query) use ($startDate, $endDate): void {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }, 'conversions.earnings.payout']); // Eager load the full relationship for paid amounts

        $events = $query->get();

        return $events->map(function (Event $event) use ($startDate, $endDate, $events): Stat {
            $conversionsCount = $event->conversions->count();

            $clickCount = $event->campaign->clicks()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate click-based conversion rate
            $clickBasedRate = $clickCount > 0 ? round(($conversionsCount / $clickCount) * 100, 2) : 0;

            // Find previous event based on sort_order for funnel analysis
            $previousEvent = $events->where('sort_order', '<', $event->sort_order)->sortByDesc('sort_order')->first();

            if ($previousEvent) {
                $baseCount = $previousEvent->conversions->count();
                $conversionRate = $baseCount > 0 ? round(($conversionsCount / $baseCount) * 100, 2) : 0;
                $relativeToText = "vs {$previousEvent->label}";
            } else {
                $conversionRate = $clickBasedRate;
                $relativeToText = 'vs Clicks';
            }

            // Calculate paid commission amounts by filtering for successful payouts
            $paidEarnings = $event->conversions->flatMap->earnings->where('payout.status', PayoutStatus::SUCCESS);
            $totalUserAmount = $paidEarnings->where('type', EarningType::USER)->sum('amount');
            $totalReferAmount = $paidEarnings->where('type', EarningType::REFER)->sum('amount');

            $description = [
                "$conversionRate% ($relativeToText)",
                'Paid User: ₹'.number_format($totalUserAmount),
                'Paid Refer: ₹'.number_format($totalReferAmount),
            ];

            return Stat::make($event->label, $conversionsCount)
                ->description(implode(' | ', $description))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->getColorBasedOnRate($conversionRate));
        })->toArray();
    }

    private function getColorBasedOnRate(float $rate): string
    {
        if ($rate >= 50) {
            return 'success';
        }
        if ($rate >= 25) {
            return 'warning';
        }

        return 'danger';
    }
}
