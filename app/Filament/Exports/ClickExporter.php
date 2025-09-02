<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Click;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;

final class ClickExporter extends Exporter
{
    protected static ?string $model = Click::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('campaign.id')->label('campaign_id'),
            ExportColumn::make('campaign.name')->label('campaign'),
            ExportColumn::make('id')->label('click_id'),
            ExportColumn::make('commission')
                ->label('commission')
                ->state(function (Click $record): string {
                    $eventCommissions = [];

                    foreach ($record->campaign->events as $event) {
                        $totalCommission = $event->user_amount + $event->refer_amount;
                        $referShare = $event->refer_amount;

                        if ($record->refer_id && $event->is_commission_split_allowed && isset($record->refer->commission_split_settings[$event->id])) {
                            $customReferShare = $record->refer->commission_split_settings[$event->id];
                            if (is_numeric($customReferShare)) {
                                $min = $event->min_refer_commission;
                                $max = $event->max_refer_commission;
                                $validatedReferShare = max($min, (int) $customReferShare);
                                $referShare = min($max, $validatedReferShare);
                            }
                        }

                        $userCommission = $totalCommission - $referShare;

                        $commissionString = "{$event->label} <>  user: {$userCommission}";
                        if ($record->refer_id !== null) {
                            $commissionString .= " | refer: {$referShare}";
                        }
                        $eventCommissions[] = $commissionString;
                    }

                    return implode('; ', $eventCommissions);
                }),
            ExportColumn::make('upi')->label('upi'),
            ExportColumn::make('extra_input_1')->label('extra_input_1'),
            ExportColumn::make('extra_input_2')->label('extra_input_2'),
            ExportColumn::make('extra_input_3')->label('extra_input_3'),
            ExportColumn::make('refer.upi')->label('refer_upi'),
            ExportColumn::make('refer.mobile')->label('refer_mobile'),
            ExportColumn::make('refer.id')->label('refer_code'),
            ExportColumn::make('refer.telegram_url')->label('refer_telegram'),
            ExportColumn::make('ip_address')->label('ip_address'),
            ExportColumn::make('created_at')->label('click_time')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('d M, Y h:i:s A')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your click export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.'; // codeCoverageIgnore
        }

        return $body;
    }
}
