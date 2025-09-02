<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Conversion;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;

final class ConversionExporter extends Exporter
{
    protected static ?string $model = Conversion::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('is_valid')->label('valid'),
            ExportColumn::make('click.campaign.id')->label('campaign_id'),
            ExportColumn::make('click.campaign.name')->label('campaign'),
            ExportColumn::make('id')->label('conversion_id'),
            ExportColumn::make('event.param')->label('event'),
            ExportColumn::make('user_amount')
                ->label('user_amount')
                ->state(fn (Conversion $record): int => $record->calculated_amounts['user']),
            ExportColumn::make('refer_amount')
                ->label('refer_amount')
                ->state(fn (Conversion $record): int => $record->calculated_amounts['refer']),
            ExportColumn::make('click.id')->label('click_id'),
            ExportColumn::make('click.upi')->label('upi'),
            ExportColumn::make('click.extra_input_1')->label('extra_input_1'),
            ExportColumn::make('click.extra_input_2')->label('extra_input_2'),
            ExportColumn::make('click.extra_input_3')->label('extra_input_3'),
            ExportColumn::make('click.refer.upi')->label('refer_upi'),
            ExportColumn::make('click.refer.mobile')->label('refer_mobile'),
            ExportColumn::make('click.refer.id')->label('refer_code'),
            ExportColumn::make('click.refer.telegram_url')->label('refer_telegram'),
            ExportColumn::make('click.ip_address')->label('ip_address'),
            ExportColumn::make('ip_address')->label('conversion_ip_address'),
            ExportColumn::make('reason')->label('invalid_reason'),
            ExportColumn::make('click.created_at')->label('click_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
            ExportColumn::make('created_at')->label('conversion_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your conversion export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.'; // codeCoverageIgnore
        }

        return $body;
    }
}
