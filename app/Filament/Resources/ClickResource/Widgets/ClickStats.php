<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClickResource\Widgets;

use App\Models\Click;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Override;

final class ClickStats extends StatsOverviewWidget
{
    #[Override]
    protected function getCards(): array
    {
        $earliestClick = Click::oldest()->first();
        $clicksTrendAllTime = Trend::model(Click::class)
            ->between(
                start: $earliestClick ? $earliestClick->created_at : now(),
                end: now(),
            )
            ->perDay()
            ->count();

        $clicksTrendOverMonthPerDay = Trend::model(Click::class)
            ->between(
                start: Carbon::make(now())->startOfMonth(),
                end: now(),
            )
            ->perDay()
            ->count();

        $clicksTrendTodayPerHour = Trend::model(Click::class)
            ->between(
                start: Carbon::make(now())->startOfDay(),
                end: now(),
            )
            ->perHour()
            ->count();

        return [
            Stat::make('Total Clicks', Click::count())
                ->description('Number of clicks')
                ->descriptionIcon('heroicon-m-arrow-path', IconPosition::Before)
                ->color('info')
                ->chart(
                    $clicksTrendAllTime
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Clicks This Month', Click::whereMonth('created_at', now()->month)->count())
                ->description('Number of clicks this month')
                ->descriptionIcon('heroicon-m-calendar-date-range', IconPosition::Before)
                ->color('warning')
                ->chart(
                    $clicksTrendOverMonthPerDay
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Clicks Today', Click::whereDate('created_at', Carbon::today())->count())
                ->description('Number of clicks today')
                ->descriptionIcon('heroicon-m-calendar', IconPosition::Before)
                ->color('success')
                ->chart(
                    $clicksTrendTodayPerHour
                        ->map(fn (TrendValue $value): mixed => $value->aggregate)
                        ->toArray()
                ),
        ];
    }
}
