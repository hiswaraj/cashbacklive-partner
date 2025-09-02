<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UPIBlockListResource\Pages\ListUPIBlockList;
use App\Models\UPIBlockList;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

final class UPIBlockListResource extends Resource
{
    protected static ?string $model = UPIBlockList::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'UPI Blocklist';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('string')
                    ->label('UPI / Mobile')
                    ->searchable(),

                TextColumn::make('block_reason')
                    ->limit(50)
                    ->searchable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListUPIBlockList::route('/'),
        ];
    }
}
