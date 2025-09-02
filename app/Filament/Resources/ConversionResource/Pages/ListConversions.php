<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConversionResource\Pages;

use App\Filament\Resources\ConversionResource;
use App\Filament\Resources\ConversionResource\Widgets\ConversionStats;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListConversions extends ListRecords
{
    protected static string $resource = ConversionResource::class;

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ConversionStats::class,
        ];
    }
}
