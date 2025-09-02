<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

final class GlobalLinks extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|UnitEnum|null $navigationGroup = 'Miscellaneous';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.global-links';

    /**
     * @return array<string, array<string, string>>
     */
    public function getGlobalLinks(): array
    {
        return [
            'refer' => route('refer.index'),
            'campaign_tracker' => route('campaign-tracker.index'),
            'refer_tracker' => route('refer-tracker.index'),
        ];
    }
}
