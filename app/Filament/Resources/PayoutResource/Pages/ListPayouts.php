<?php

declare(strict_types=1);

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use App\Filament\Resources\PayoutResource\Widgets\PayoutsStats;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayoutsStats::class,
        ];
    }
}
