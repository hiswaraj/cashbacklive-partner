<?php

declare(strict_types=1);

namespace App\Filament\Resources\EarningResource\Pages;

use App\Filament\Resources\EarningResource;
use App\Filament\Resources\EarningResource\Widgets\EarningStats;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListEarnings extends ListRecords
{
    protected static string $resource = EarningResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            EarningStats::class,
        ];
    }
}
