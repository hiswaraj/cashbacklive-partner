<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignResource\Widgets;

use App\Enums\AccessPolicy;
use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Override;

final class CampaignOverview extends BaseWidget
{
    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Total Campaigns', Campaign::count())
                ->icon('heroicon-o-rectangle-stack'),
            Stat::make('Active Campaigns', Campaign::where('is_active', true)->count())
                ->icon('heroicon-o-check-circle'),
            Stat::make('Public Campaigns', Campaign::where('access_policy', AccessPolicy::PUBLIC)->count())
                ->icon('heroicon-o-eye'),
        ];
    }
}
