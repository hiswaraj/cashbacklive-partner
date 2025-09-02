<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClickResource\Pages;

use App\Filament\Resources\ClickResource;
use App\Filament\Resources\ClickResource\Widgets\ClickStats;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListClicks extends ListRecords
{
    protected static string $resource = ClickResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [ClickStats::class,
        ];
    }
}
