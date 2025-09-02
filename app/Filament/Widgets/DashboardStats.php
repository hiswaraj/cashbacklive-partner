<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Earning;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Override;

final class DashboardStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    #[Override]
    protected function getCards(): array
    {
        $createdAtFilter = $this->pageFilters['created_at'] ?? null;
        if (! $createdAtFilter) {
            // Default to a reasonable range if no filter is set
            $createdAtFilter = now()->subDays(6)->format('d/m/Y').' - '.now()->format('d/m/Y');
        }
        $createdAtFilterParts = explode(' - ', $createdAtFilter);
        $startDate = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[0])->startOfDay();
        $endDate = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[1])->endOfDay();

        // Base queries with date range
        $clickQuery = Click::query()->whereBetween('created_at', [$startDate, $endDate]);
        $convQuery = Conversion::query()->whereBetween('created_at', [$startDate, $endDate]);
        $earningQuery = Earning::query()->whereBetween('created_at', [$startDate, $endDate]);

        // Apply campaign filter if selected
        if (! empty($this->pageFilters['campaign'])) {
            $campaignId = $this->pageFilters['campaign'];
            $clickQuery->where('campaign_id', $campaignId);
            $convQuery
                ->whereHas('click', fn (Builder $query) => $query
                    ->where('campaign_id', $campaignId));
            $earningQuery
                ->whereHas('conversion.click', fn (Builder $query) => $query
                    ->where('campaign_id', $campaignId));
        }

        // Calculate stats
        $clickCount = (clone $clickQuery)->count();
        $convCount = (clone $convQuery)->count();

        // Calculate paid amounts by querying earnings linked to successful payouts
        $paidUserEarningsQuery = (clone $earningQuery)
            ->where('type', EarningType::USER)
            ->whereHas('payout', fn (Builder $q) => $q
                ->where('status', PayoutStatus::SUCCESS));
        $paidReferEarningsQuery = (clone $earningQuery)
            ->where('type', EarningType::REFER)
            ->whereHas('payout', fn (Builder $q) => $q
                ->where('status', PayoutStatus::SUCCESS));

        $totalUserAmount = (int) $paidUserEarningsQuery->sum('amount');
        $totalReferAmount = (int) $paidReferEarningsQuery->sum('amount');

        // Prepare trend data
        $clickTrend = Trend::query($clickQuery)
            ->between($startDate, $endDate)
            ->perDay()
            ->count();
        $convTrend = Trend::query($convQuery)
            ->between($startDate, $endDate)
            ->perDay()
            ->count();
        $userAmountTrend = Trend::query($paidUserEarningsQuery)
            ->between($startDate, $endDate)
            ->perDay()
            ->sum('amount');
        $referAmountTrend = Trend::query($paidReferEarningsQuery)
            ->between($startDate, $endDate)
            ->perDay()
            ->sum('amount');

        return [
            Stat::make('Total Clicks', $clickCount)
                ->description('No. of clicks')
                ->descriptionIcon('heroicon-m-cursor-arrow-ripple', IconPosition::Before)
                ->color('info')
                ->chart($clickTrend->map(fn (TrendValue $value): int => $value->aggregate)->toArray()),

            Stat::make('Total Conversions', $convCount)
                ->description('No. of Conversions')
                ->descriptionIcon('heroicon-m-check-badge', IconPosition::Before)
                ->color('primary')
                ->chart($convTrend->map(fn (TrendValue $value): int => $value->aggregate)->toArray()),

            Stat::make('Total User Payouts', '₹ '.number_format($totalUserAmount))
                ->description('Successful user payouts')
                ->descriptionIcon('heroicon-m-user', IconPosition::Before)
                ->color('success')
                ->chart($userAmountTrend->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray()), // FIXED

            Stat::make('Total Referrer Payouts', '₹ '.number_format($totalReferAmount))
                ->description('Successful refer payouts')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->color('warning')
                ->chart($referAmountTrend->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray()), // FIXED
        ];
    }
}
