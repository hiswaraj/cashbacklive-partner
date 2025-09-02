<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Exports\ConversionExporter;
use App\Filament\Resources\ConversionResource\Pages\ListConversions;
use App\Models\Campaign;
use App\Models\Conversion;
use App\Models\Event;
use App\Services\Payment\PaymentService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Override;

final class ConversionResource extends Resource
{
    protected static ?string $model = Conversion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 3;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_valid')
                    ->boolean()
                    ->label('Valid'),

                TextColumn::make('click_id')
                    ->searchable(),

                ImageColumn::make('click.campaign.logo_path')
                    ->label('Campaign Logo')
                    ->disk('public')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('click.campaign.name')
                    ->label('Campaign Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event.label'),

                TextColumn::make('user_amount')
                    ->label('User Amount')
                    ->money('INR')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Conversion $record): int => $record->calculated_amounts['user']),

                TextColumn::make('refer_amount')
                    ->label('Referrer Amount')
                    ->money('INR')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Conversion $record): int => $record->calculated_amounts['refer']),

                TextColumn::make('click.upi')
                    ->label('User UPI')
                    ->searchable(),

                TextColumn::make('click.refer.upi')
                    ->label('Refer UPI')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('click.refer.mobile')
                    ->label('Refer Mobile')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('click.refer.telegram_url')
                    ->label('Refer Telegram')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('click.extra_input_1')
                    ->label('Extra Input 1')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('click.extra_input_2')
                    ->label('Extra Input 2')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('click.extra_input_3')
                    ->label('Extra Input 3')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Conversion Time')
                    ->dateTime('d M, Y h:i:s A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('click.created_at')
                    ->label('Click Time')
                    ->dateTime('d M, Y h:i:s A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('click.ip_address')
                    ->label('Click IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('Conv. IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reason')
                    ->label('Invalid Reason')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('campaign_and_events_filter')
                    ->schema([
                        Select::make('campaigns')
                            ->multiple()
                            ->preload()
                            ->options(Campaign::query()->pluck('name', 'id')),

                        Select::make('has_events')
                            ->label('Has These Events')
                            ->helperText('Filter for clicks that have conversions for ALL selected events.')
                            ->multiple()
                            ->preload()
                            ->options(function (Get $get) {
                                $campaignIds = $get('campaigns');

                                if (empty($campaignIds)) {
                                    return [];
                                }

                                return Event::whereIn('campaign_id', $campaignIds)
                                    ->with('campaign:id,name')
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->mapWithKeys(function (Event $event) {
                                        $label = "{$event->label} ({$event->campaign->name})";

                                        return [$event->id => $label];
                                    })
                                    ->unique();
                            })
                            ->hidden(fn (Get $get): bool => empty($get('campaigns'))),

                        Select::make('doesnt_has_events')
                            ->label('Missing These Events')
                            ->helperText('Filter for clicks that DO NOT have conversions for ANY of selected events.')
                            ->multiple()
                            ->preload()
                            ->options(function (Get $get) {
                                $campaignIds = $get('campaigns');

                                if (empty($campaignIds)) {
                                    return [];
                                }

                                return Event::whereIn('campaign_id', $campaignIds)
                                    ->with('campaign:id,name')
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->mapWithKeys(function (Event $event) {
                                        $label = "{$event->label} ({$event->campaign->name})";

                                        return [$event->id => $label];
                                    })
                                    ->unique();
                            })
                            ->hidden(fn (Get $get): bool => empty($get('campaigns'))),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['campaigns'] ?? null,
                                fn (Builder $query, array $campaignIds): Builder => $query->whereHas('click', fn (Builder $q) => $q->whereIn('campaign_id', $campaignIds))
                            )
                            ->when(
                                $data['has_events'] ?? null,
                                function (Builder $query, array $eventIds): Builder {
                                    return $query->whereHas('click', function (Builder $clickQuery) use ($eventIds) {
                                        foreach ($eventIds as $eventId) {
                                            $clickQuery->whereHas('conversions', function (Builder $convQuery) use ($eventId) {
                                                $convQuery->where('event_id', $eventId);
                                            });
                                        }
                                    });
                                }
                            )
                            ->when(
                                $data['doesnt_has_events'] ?? null,
                                fn (Builder $query, array $eventIds): Builder => $query->whereHas('click', function (Builder $q) use ($eventIds) {
                                    $q->whereDoesntHave('conversions', function (Builder $convQ) use ($eventIds) {
                                        $convQ->whereIn('event_id', $eventIds);
                                    });
                                })
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['campaigns'] ?? null) {
                            $campaignNames = Campaign::whereIn('id', $data['campaigns'])->pluck('name')->join(', ', '');
                            // This creates a dismissible indicator specifically for the 'campaign' field.
                            // When dismissed, it also removes the dependent event filters.
                            $indicators[] = "Campaigns: {$campaignNames}";
                        }

                        if ($data['has_events'] ?? null) {
                            $events = Event::whereIn('id', $data['has_events'])
                                ->with('campaign:id,name')
                                ->get()
                                ->map(fn (Event $event): string => "{$event->label} ({$event->campaign->name})")
                                ->join(', ');
                            // This creates a dismissible indicator specifically for the 'has_events' field.
                            $indicators[] = Indicator::make('Has Events: '.$events)
                                ->removeField('has_events');
                        }

                        if ($data['doesnt_has_events'] ?? null) {
                            $events = Event::whereIn('id', $data['doesnt_has_events'])
                                ->with('campaign:id,name')
                                ->get()
                                ->map(fn (Event $event): string => "{$event->label} ({$event->campaign->name})")
                                ->join(', ');
                            // This creates a dismissible indicator specifically for the 'doesnt_has_events' field.
                            $indicators[] = Indicator::make('Missing Events: '.$events)
                                ->removeField('doesnt_has_events');
                        }

                        return $indicators;
                    }),

                Filter::make('click_created_at_filter')
                    ->schema([
                        DateRangePicker::make('click_created_at')
                            ->label('Click Time')
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

                                return $query->whereHas('click', function (Builder $q) use ($clickTimeStart, $clickTimeEnd): void {
                                    $q->whereBetween('created_at', [$clickTimeStart, $clickTimeEnd]);
                                });
                            }
                        ),
                    ),

                Filter::make('created_at_filter')
                    ->schema([
                        DateRangePicker::make('created_at')
                            ->label('Conversion Time')
                            ->drops(DropDirection::UP)
                            ->opens(OpenDirection::CENTER)
                            ->maxDate(Carbon::now())
                            ->autoApply(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_at'] ?? null) {
                            $createdAtFilter = $data['created_at'];
                            $indicators[] = "Conversion Time: {$createdAtFilter}";
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_at'] ?? null,
                            function (Builder $query, string $createdAtFilter): Builder {
                                $createdAtFilterParts = explode(' - ', $createdAtFilter);

                                $conversionTimeStart = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[0])
                                    ->startOfDay();
                                $conversionTimeEnd = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[1])
                                    ->endOfDay();

                                return $query->whereBetween('created_at', [$conversionTimeStart, $conversionTimeEnd]);
                            }
                        ),
                    ),

                TernaryFilter::make('is_valid')->label('Valid'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(ConversionExporter::class)
                    ->label('Export selected'),

                DeleteBulkAction::make(),

                ExportAction::make()
                    ->exporter(ConversionExporter::class)
                    ->label('Export all'),
            ])
            ->recordActions([
                Action::make('toggle_is_valid')
                    ->label(fn (Conversion $record): string => $record->is_valid ? 'Mark as Invalid' : 'Mark as Valid')
                    ->icon(fn (Conversion $record): string => $record->is_valid ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Conversion $record): string => $record->is_valid ? 'danger' : 'success')
                    ->requiresConfirmation()
                    // Add a form that only appears when marking as invalid
                    ->schema(function (Conversion $record): array {
                        if (! $record->is_valid) {
                            return []; // No form needed when marking as valid
                        }

                        return [
                            TextInput::make('reason')
                                ->label('Reason for Invalidation')
                                ->required()
                                ->default('Marked as invalid by admin')
                                ->maxLength(255),
                        ];
                    })
                    ->action(function (Conversion $record, array $data): void {
                        try {
                            // By wrapping the logic in a transaction, we ensure all DB operations are atomic.
                            DB::transaction(function () use ($record, $data) {
                                if ($record->is_valid) {
                                    // Logic to MARK AS INVALID
                                    // 1. Eager load earnings to prevent extra queries.
                                    $record->load('earnings');

                                    // 2. Check if any earning is already part of a payout.
                                    if ($record->earnings->whereNotNull('payout_id')->isNotEmpty()) {
                                        throw new Exception('This conversion is part of a completed or pending payout and cannot be marked as invalid.');
                                    }

                                    // 3. Delete all associated earnings.
                                    foreach ($record->earnings as $earning) {
                                        $earning->delete();
                                    }

                                    // 4. Update the conversion itself.
                                    $record->is_valid = false;
                                    $record->reason = $data['reason'];
                                    $record->save();

                                } else {
                                    // Logic to MARK AS VALID
                                    // 1. Update the conversion itself.
                                    $record->is_valid = true;
                                    $record->reason = null;
                                    $record->save();

                                    // 2. Create the earnings. The PaymentService will handle this.
                                    // We pass 'force' flags as false to prevent instant payouts from this action.
                                    app(PaymentService::class)->processEarningsForConversion(
                                        conversion: $record,
                                        forceInstantPayUser: false,
                                        forceInstantPayReferrer: false
                                    );
                                }
                            });

                            // Send notification ONLY if the transaction was successful.
                            if ($record->wasChanged('is_valid')) {
                                $message = $record->is_valid
                                    ? 'Conversion marked as valid and earnings created.'
                                    : 'Conversion marked as invalid and associated earnings deleted.';
                                Notification::make()->title('Success')->body($message)->success()->send();
                            }

                        } catch (Exception $e) {
                            // This will catch the exception from the transaction.
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListConversions::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayTotalCount = self::getModel()::whereBetween(
            'created_at',
            [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]
        )->count();

        if ($todayTotalCount !== 0) {
            // FIXME: codeCoverageIgnore
            // @codeCoverageIgnoreStart
            return (string) $todayTotalCount;
            // @codeCoverageIgnoreEnd
        }

        return null;
    }
}
