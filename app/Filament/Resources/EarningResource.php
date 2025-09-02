<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Filament\Exports\EarningExporter;
use App\Filament\Resources\EarningResource\Pages;
use App\Models\Earning;
use App\Models\Payout;
use App\Services\Payment\PaymentService;
use BackedEnum;
use Exception;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Override;

final class EarningResource extends Resource
{
    protected static ?string $model = Earning::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?int $navigationSort = 4;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('conversion.click.campaign.logo_path')->label('Campaign Logo')
                    ->disk('public')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conversion.click.campaign.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conversion.click.id')->label('Click ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conversion.event.label')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->toggleable(),

                // This is the core "Derived Status" column.
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (Earning $record): string => $record->payout?->status->getColor() ?? 'info')
                    ->icon(fn (Earning $record): string => $record->payout?->status->getIcon() ?? 'heroicon-o-question-mark-circle'),

                TextColumn::make('upi')->label('UPI')
                    ->copyable()
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function (Builder $q) use ($search) {
                                $q->whereHas(
                                    'conversion.click',
                                    fn (Builder $clickQuery) => $clickQuery
                                        ->where('upi', 'like', "%$search%"))
                                    ->orWhereHas(
                                        'conversion.click.refer',
                                        fn (Builder $referQuery) => $referQuery
                                            ->where('upi', 'like', "%$search%"));
                            });
                        }
                    ),

                TextColumn::make('created_at')->label('Earned On')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('conversion.created_at')->label('Conversion Time')
                    ->dateTime('d M, Y h:i:s A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conversion.click.created_at')->label('Click Time')
                    ->dateTime('d M, Y h:i:s A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('campaign')
                    ->label('Campaigns')
                    ->multiple()
                    ->preload()
                    ->relationship('conversion.click.campaign', 'name'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(function (): array {
                        $options = ['unpaid' => 'Unpaid'];

                        // Merge with all cases from the PayoutStatus enum.
                        foreach (PayoutStatus::cases() as $case) {
                            $options[$case->value] = ucfirst($case->value);
                        }

                        return $options;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['value'],
                            function (Builder $query, string $status): Builder {
                                return match ($status) {
                                    'unpaid' => $query->whereNull('payout_id'),
                                    default => $query->whereHas('payout', fn (Builder $q) => $q->where('status', $status)),
                                };
                            }
                        )
                    ),

                SelectFilter::make('type')
                    ->options(EarningType::class),

                Filter::make('amount_range')
                    ->schema([
                        TextInput::make('min_amount')
                            ->numeric()
                            ->label('Minimum Amount')
                            ->placeholder('Enter minimum amount'),
                        TextInput::make('max_amount')
                            ->numeric()
                            ->label('Maximum Amount')
                            ->placeholder('Enter maximum amount'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['min_amount'],
                            fn (Builder $query, int $min_amount): Builder => $query->where('amount', '>=', $min_amount)
                        )
                        ->when(
                            $data['max_amount'],
                            fn (Builder $query, int $max_amount): Builder => $query->where('amount', '<=', $max_amount)
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators['min_amount'] = 'Min amount: ₹'.$data['min_amount'];
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators['max_amount'] = 'Max amount: ₹'.$data['max_amount'];
                        }

                        return $indicators;
                    }),

                Filter::make('click_created_at_filter')
                    ->schema([
                        DateRangePicker::make('click_created_at')
                            ->label('Click Time')
                            ->drops(DropDirection::UP)
                            ->opens(OpenDirection::CENTER)
                            ->maxDate(Carbon::now())
                            ->autoApply(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['click_created_at'] ?? null) {
                            $createdAtFilter = $data['click_created_at'];
                            $indicators[] = "Click Time: {$createdAtFilter}";
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['click_created_at'] ?? null,
                            function (Builder $query, string $clickCreatedAtFilter): Builder {
                                $clickCreatedAtFilterParts = explode(' - ', $clickCreatedAtFilter);

                                $clickTimeStart = Carbon::createFromFormat('d/m/Y', $clickCreatedAtFilterParts[0])
                                    ->startOfDay();
                                $clickTimeEnd = Carbon::createFromFormat('d/m/Y', $clickCreatedAtFilterParts[1])
                                    ->endOfDay();

                                return $query->whereHas('conversion.click', function (Builder $q) use ($clickTimeStart, $clickTimeEnd): void {
                                    $q->whereBetween('created_at', [$clickTimeStart, $clickTimeEnd]);
                                });
                            }
                        ),
                    ),

                Filter::make('conversion_created_at_filter')
                    ->schema([
                        DateRangePicker::make('conversion_created_at')
                            ->label('Conversion Time')
                            ->drops(DropDirection::UP)
                            ->opens(OpenDirection::CENTER)
                            ->maxDate(Carbon::now())
                            ->autoApply(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['conversion_created_at'] ?? null) {
                            $createdAtFilter = $data['conversion_created_at'];
                            $indicators[] = "Conversion Time: {$createdAtFilter}";
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['conversion_created_at'] ?? null,
                            function (Builder $query, string $conversionCreatedAtFilter): Builder {
                                $conversionCreatedAtFilterParts = explode(' - ', $conversionCreatedAtFilter);

                                $conversionTimeStart = Carbon::createFromFormat('d/m/Y', $conversionCreatedAtFilterParts[0])
                                    ->startOfDay();
                                $conversionTimeEnd = Carbon::createFromFormat('d/m/Y', $conversionCreatedAtFilterParts[1])
                                    ->endOfDay();

                                return $query->whereHas('conversion', function (Builder $q) use ($conversionTimeStart, $conversionTimeEnd): void {
                                    $q->whereBetween('created_at', [$conversionTimeStart, $conversionTimeEnd]);
                                });
                            }
                        ),
                    ),

                Filter::make('created_at_filter')
                    ->schema([
                        DateRangePicker::make('created_at')
                            ->label('Earned On')
                            ->drops(DropDirection::UP)
                            ->opens(OpenDirection::CENTER)
                            ->maxDate(Carbon::now())
                            ->autoApply(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_at'] ?? null) {
                            $createdAtFilter = $data['created_at'];
                            $indicators[] = "Earned On: {$createdAtFilter}";
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_at'] ?? null,
                            function (Builder $query, string $createdAtFilter): Builder {
                                $createdAtFilterParts = explode(' - ', $createdAtFilter);

                                $earningTimeStart = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[0])
                                    ->startOfDay();
                                $earningTimeEnd = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[1])
                                    ->endOfDay();

                                return $query->whereBetween('created_at', [$earningTimeStart, $earningTimeEnd]);
                            }
                        ),
                    ),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(EarningExporter::class)
                    ->label('Export selected'),

                DeleteBulkAction::make(),

                ExportAction::make()
                    ->exporter(EarningExporter::class)
                    ->label('Export all'),

                BulkAction::make('initiate_batch_payout')
                    ->label('Initiate Batch Payout')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will group the selected earnings by UPI and create a new payout for each group. This action is irreversible.')
                    ->schema([
                        Textarea::make('comment')
                            ->label('Payout Comment')
                            ->placeholder('e.g., Campaign x Payout')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data, PaymentService $paymentService) {
                        // Filter only for records that are actually unpaid
                        $allEarningsWithoutPayout = $records->whereNull('payout_id');

                        if ($allEarningsWithoutPayout->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('No Unpaid Earnings')
                                ->body('All selected records have already been paid out.')
                                ->send();

                            return;
                        }

                        // Group by UPI to create separate payouts
                        $groupedByUpi = $allEarningsWithoutPayout->groupBy('upi');

                        foreach ($groupedByUpi as $upi => $earnings) {
                            try {
                                $paymentService->initiatePayoutForEarnings(
                                    earnings: $earnings,
                                    comment: $data['comment']
                                );
                                Notification::make()
                                    ->success()
                                    ->title('Payout Initiated')
                                    ->body("Payout for {$upi} has been queued.")
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Payout Failed for UPI: '.$upi)
                                    ->body($e
                                        ->getMessage())
                                    ->send();
                                report($e);
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('markFailedAsUnpaid')
                    ->label('Mark Failed as Unpaid')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Failed Earnings as Unpaid')
                    ->modalDescription('This action will find any selected earnings that belong to a FAILED payout. It will then delete that payout record and mark the corresponding earnings as "Unpaid", making them available for a new payout attempt. This action is irreversible.')
                    ->action(function (Collection $records) {
                        // Get unique, non-null payout IDs from the selected records.
                        $payoutIds = $records->pluck('payout_id')->filter()->unique();

                        if ($payoutIds->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('No Payouts Found')
                                ->body('The selected earnings are not associated with any payouts.')
                                ->send();

                            return;
                        }

                        // Find all Payouts that are FAILED from the list of IDs.
                        $failedPayouts = Payout::whereIn('id', $payoutIds)
                            ->where('status', PayoutStatus::FAILED)
                            ->get();

                        if ($failedPayouts->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('No Failed Payouts Found')
                                ->body('None of the selected earnings belong to a payout with a "Failed" status.')
                                ->send();

                            return;
                        }

                        $failedPayoutIds = $failedPayouts->pluck('id');
                        $earningsToUpdateCount = Earning::whereIn('payout_id', $failedPayoutIds)->count();

                        try {
                            DB::transaction(function () use ($failedPayoutIds) {
                                // Mark all associated earnings as unpaid by detaching them from the payout.
                                Earning::whereIn('payout_id', $failedPayoutIds)->update(['payout_id' => null]);

                                // Now, delete the failed payout records.
                                Payout::whereIn('id', $failedPayoutIds)->delete();
                            });

                            Notification::make()
                                ->success()
                                ->title('Action Successful')
                                ->body("Marked {$earningsToUpdateCount} earnings as unpaid and deleted {$failedPayoutIds->count()} failed payout(s).")
                                ->send();

                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('An Error Occurred')
                                ->body('The operation could not be completed. Please check the logs for more details.')
                                ->send();
                            report($e);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEarnings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayTotal = self::getModel()::whereBetween(
            'created_at',
            [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]
        )->sum('amount');

        if ($todayTotal !== 0) {
            // FIXME: codeCoverageIgnore
            // @codeCoverageIgnoreStart
            return '₹'.number_format((int) $todayTotal, 2);
            // @codeCoverageIgnoreEnd
        }

        return null;
    }

    /**
     * This is the critical part that ensures the table groups appear in the
     * correct logical order: Unpaid -> Failed -> Pending -> Success.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['payout', 'conversion.event', 'conversion.click.campaign', 'conversion.click.refer']);
    }
}
