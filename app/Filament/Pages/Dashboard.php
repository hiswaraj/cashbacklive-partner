<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Campaign;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

final class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('campaign')
                    ->label('Campaign')
                    ->options(Campaign::pluck('name', 'id'))
                    ->live()
                    ->searchable()
                    ->columnSpan(1),
                DateRangePicker::make('created_at')
                    ->drops(DropDirection::AUTO)
                    ->opens(OpenDirection::CENTER)
                    ->startDate(now()->subDays(7))
                    ->endDate(now())
                    ->autoApply(),
            ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
