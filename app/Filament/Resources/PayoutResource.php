<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PayoutStatus;
use App\Filament\Exports\PayoutExporter;
use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Rules\UPI as UpiRule;
use App\Services\Payment\PaymentService;
use BackedEnum;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Override;

final class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 5;

    /**
     * Payouts are system-generated, so the form is replaced by a read-only Infolist.
     */
    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('Payout ID'),
                        TextEntry::make('upi')->label('UPI Address')->copyable(),
                        TextEntry::make('total_amount')->money('INR'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('payment_gateway')->badge(),
                        TextEntry::make('comment'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                    ]),
                Section::make('Gateway & Reference Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference_id')->label('Reference ID')->copyable(),
                        TextEntry::make('payment_id')->label('Gateway Payment ID')->copyable(),
                        TextEntry::make('api_response')->columnSpanFull(),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Payout ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('upi')
                    ->label('UPI')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('payment_gateway')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_id')
                    ->label('Gateway Payment ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('comment')
                    ->searchable()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Initiated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PayoutStatus::class),

                SelectFilter::make('payment_gateway')
                    ->options(fn (): array => Payout::query()
                        ->distinct()
                        ->pluck('payment_gateway', 'payment_gateway')
                        ->all()
                    ),

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
                            fn (Builder $query, int $min_amount): Builder => $query->where('total_amount', '>=', $min_amount)
                        )
                        ->when(
                            $data['max_amount'],
                            fn (Builder $query, int $max_amount): Builder => $query->where('total_amount', '<=', $max_amount)
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
                            $indicators[] = "Initiated At: {$createdAtFilter}";
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
                    ->exporter(PayoutExporter::class)
                    ->label('Export selected'),

                DeleteBulkAction::make(),

                ExportAction::make()
                    ->exporter(PayoutExporter::class)
                    ->label('Export all'),
            ])
            ->recordActions([
                Action::make('retry_payout')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    // Only show this action if ALL selected records are FAILED.
                    ->visible(fn (Payout $record): bool => $record->status === PayoutStatus::FAILED && $record->earnings->count() > 0)
                    ->schema([
                        TextInput::make('corrected_upi') // Renamed for clarity
                            ->label('Corrected UPI')
                            ->required()
                            ->rule(new UpiRule()) // Added the missing validation
                            ->default(fn (Payout $record) => $record->upi),

                        Textarea::make('comment')
                            ->label('Comment for New Payout')
                            ->helperText('This comment will be applied to the new payout created.')
                            ->default(fn (Payout $record) => 'retry - '.$record->comment)
                            ->rules(['nullable', 'string', 'ascii']),
                    ])
                    ->action(function (Payout $record, array $data, PaymentService $paymentService): void {
                        try {
                            // Call the new, safe, transactional service method
                            $paymentService->retryFailedPayout(
                                $record,
                                $data['corrected_upi'],
                                $data['comment']
                            );

                            Notification::make()->success()->title('Payout Re-queued')->body('A new payout has been created with the corrected information.')->send();
                        } catch (Exception $e) {
                            Notification::make()->danger()->title('An Error Occurred')->body($e->getMessage())->send();
                            report($e);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('earnings');
    }
}
