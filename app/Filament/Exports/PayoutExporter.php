<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;

final class PayoutExporter extends Exporter
{
    protected static ?string $model = Payout::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('id'),
            ExportColumn::make('upi')->label('upi'),
            ExportColumn::make('total_amount')->label('total_amount'),
            ExportColumn::make('status')
                ->label('status')
                ->formatStateUsing(fn (PayoutStatus $state) => $state->value),
            ExportColumn::make('payment_gateway')->label('gateway'),
            ExportColumn::make('comment')->label('comment'),
            ExportColumn::make('reference_id')->label('reference_id'),
            ExportColumn::make('payment_id')->label('payment_id'),
            ExportColumn::make('created_at')->label('initiated_at')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payout export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
