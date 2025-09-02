<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\EarningType;
use App\Models\Earning;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;

final class EarningExporter extends Exporter
{
    protected static ?string $model = Earning::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('id'),
            ExportColumn::make('conversion.click.campaign.name')->label('campaign'),
            ExportColumn::make('conversion.click.id')->label('click_id'),
            ExportColumn::make('conversion.event.param')->label('event'),
            ExportColumn::make('upi'),
            ExportColumn::make('amount')->label('amount'),
            ExportColumn::make('type')
                ->label('type')
                ->formatStateUsing(fn (EarningType $state) => $state->value),
            ExportColumn::make('status')
                ->label('status')
                ->state(fn (Earning $record): string => match (true) {
                    $record->payout_id === null => 'UNPAID',
                    default => mb_strtoupper($record->payout->status->value),
                }),
            ExportColumn::make('conversion.click.created_at')->label('click_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
            ExportColumn::make('conversion.created_at')->label('conversion_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
            ExportColumn::make('created_at')->label('earn_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your earning export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
