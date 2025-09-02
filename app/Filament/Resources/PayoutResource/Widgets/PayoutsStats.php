<?php

declare(strict_types=1);

namespace App\Filament\Resources\PayoutResource\Widgets;

use App\Models\Payout;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Override;

final class PayoutsStats extends StatsOverviewWidget
{
    #[Override]
    protected function getCards(): array
    {
        $earliestPayout = Payout::oldest()->first();
        $payoutTrendAllTime = Trend::model(Payout::class)
            ->between(
                start: $earliestPayout ? $earliestPayout->created_at : now(),
                end: now(),
            )
            ->perDay()
            ->sum('total_amount');

        $payoutTrendOverMonthPerDay = Trend::model(Payout::class)
            ->between(
                start: Carbon::make(now())->startOfMonth(),
                end: now(),
            )
            ->perDay()
            ->sum('total_amount');

        $payoutTrendTodayPerHour = Trend::model(Payout::class)
            ->between(
                start: Carbon::make(now())->startOfDay(),
                end: now(),
            )
            ->perHour()
            ->sum('total_amount');

        return [
            Stat::make('Total Payouts Amount', '₹'.Payout::sum('total_amount'))
                ->description('Total amount of earnings')
                ->descriptionIcon('heroicon-m-currency-rupee', IconPosition::Before)
                ->color('info')
                ->chart(
                    $payoutTrendAllTime
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Payout Amount This Month', '₹'.Payout::whereMonth('created_at', now()->month)->sum('total_amount'))
                ->description('Total amount this month')
                ->descriptionIcon('heroicon-m-calendar-date-range', IconPosition::Before)
                ->color('warning')
                ->chart(
                    $payoutTrendOverMonthPerDay
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Payout Amount Today', '₹'.Payout::whereDate('created_at', Carbon::today())->sum('total_amount'))
                ->description('Total amount today')
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::Before)
                ->color('success')
                ->chart(
                    $payoutTrendTodayPerHour
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
        ];
    }
}
