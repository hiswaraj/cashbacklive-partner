<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConversionResource\Widgets;

use App\Models\Conversion;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Override;

final class ConversionStats extends StatsOverviewWidget
{
    #[Override]
    protected function getCards(): array
    {
        $earliestConversion = Conversion::oldest()->first();
        $convTrendAllTime = Trend::model(Conversion::class)
            ->between(
                start: $earliestConversion ? $earliestConversion->created_at : now(),
                end: now(),
            )
            ->perDay()
            ->count();

        $convTrendOverMonthPerDay = Trend::model(Conversion::class)
            ->between(
                start: Carbon::make(now())->startOfMonth(),
                end: now(),
            )
            ->perDay()
            ->count();

        $convTrendTodayPerHour = Trend::model(Conversion::class)
            ->between(
                start: Carbon::make(now())->startOfDay(),
                end: now(),
            )
            ->perHour()
            ->count();

        return [
            Stat::make('Total Conversions', Conversion::count())
                ->description('Number of conversions')
                ->descriptionIcon('heroicon-m-arrow-path', IconPosition::Before)
                ->color('info')
                ->chart(
                    $convTrendAllTime
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Conversions This Month', Conversion::whereMonth('created_at', now()->month)->count())
                ->description('Number of conversions this month')
                ->descriptionIcon('heroicon-m-calendar-date-range', IconPosition::Before)
                ->color('warning')
                ->chart(
                    $convTrendOverMonthPerDay
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Conversions Today', Conversion::whereDate('created_at', Carbon::today())->count())
                ->description('Number of conversions today')
                ->descriptionIcon('heroicon-m-calendar', IconPosition::Before)
                ->color('success')
                ->chart(
                    $convTrendTodayPerHour
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
        ];
    }
}
