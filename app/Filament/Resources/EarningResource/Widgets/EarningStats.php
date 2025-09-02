<?php

declare(strict_types=1);

namespace App\Filament\Resources\EarningResource\Widgets;

use App\Models\Earning;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Override;

final class EarningStats extends StatsOverviewWidget
{
    #[Override]
    protected function getCards(): array
    {
        $earliestEarning = Earning::oldest()->first();
        $earningTrendAllTime = Trend::model(Earning::class)
            ->between(
                start: $earliestEarning ? $earliestEarning->created_at : now(),
                end: now(),
            )
            ->perDay()
            ->sum('amount');

        $earningTrendOverMonthPerDay = Trend::model(Earning::class)
            ->between(
                start: Carbon::make(now())->startOfMonth(),
                end: now(),
            )
            ->perDay()
            ->sum('amount');

        $earningTrendTodayPerHour = Trend::model(Earning::class)
            ->between(
                start: Carbon::make(now())->startOfDay(),
                end: now(),
            )
            ->perHour()
            ->sum('amount');

        return [
            Stat::make('Total Earnings Amount', '₹'.Earning::sum('amount'))
                ->description('Total amount of earnings')
                ->descriptionIcon('heroicon-m-currency-rupee', IconPosition::Before)
                ->color('info')
                ->chart(
                    $earningTrendAllTime
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Earning Amount This Month', '₹'.Earning::whereMonth('created_at', now()->month)->sum('amount'))
                ->description('Total amount this month')
                ->descriptionIcon('heroicon-m-calendar-date-range', IconPosition::Before)
                ->color('warning')
                ->chart(
                    $earningTrendOverMonthPerDay
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Earning Amount Today', '₹'.Earning::whereDate('created_at', Carbon::today())->sum('amount'))
                ->description('Total amount today')
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::Before)
                ->color('success')
                ->chart(
                    $earningTrendTodayPerHour
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
        ];
    }
}
