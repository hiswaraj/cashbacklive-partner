<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Exports\ClickExporter;
use App\Filament\Resources\ClickResource\Pages\ListClicks;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Override;

final class ClickResource extends Resource
{
    protected static ?string $model = Click::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static ?int $navigationSort = 2;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Click ID')
                    ->searchable(),

                ImageColumn::make('campaign.logo_path')
                    ->label('Campaign Logo')
                    ->disk('public')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('campaign.name')
                    ->label('Campaign Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commission')
                    ->label('Commission')
                    ->html()
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

                            $commissionString = "<b>{$event->label} <> </b> user: {$userCommission}";
                            if ($record->refer_id !== null) {
                                $commissionString .= " | refer: {$referShare}";
                            }
                            $eventCommissions[] = $commissionString;
                        }

                        return implode('<br>', $eventCommissions);
                    }),

                TextColumn::make('created_at')
                    ->label('Click Time')
                    ->dateTime('d M, Y h:i:s A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('upi')
                    ->label('User UPI')
                    ->searchable(),

                TextColumn::make('refer.upi')
                    ->label('Refer UPI')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('refer.mobile')
                    ->label('Refer Mobile')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('refer.telegram_url')
                    ->label('Refer Telegram')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('extra_input_1')
                    ->label('Extra Input 1')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('extra_input_2')
                    ->label('Extra Input 2')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('extra_input_3')
                    ->label('Extra Input 3')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Address')
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
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['campaigns'] ?? null,
                            fn (Builder $query, Campaign|array $campaigns): Builder => $query->whereIn('campaign_id', $campaigns)
                        )
                        ->when(
                            $data['has_events'] ?? null,
                            fn (Builder $query, Event|array $has_events): Builder => $query->whereHas('conversions',
                                fn (Builder $q) => $q->whereIn('event_id', $has_events))
                        )
                        ->when(
                            $data['doesnt_has_events'] ?? null,
                            fn (Builder $query, Event|array $doesnt_has_events): Builder => $query->whereDoesntHave('conversions',
                                fn (Builder $q) => $q->whereIn('event_id', $doesnt_has_events))
                        )
                    ),

                Filter::make('created_at_filter')
                    ->schema([
                        DateRangePicker::make('created_at')
                            ->label('Click Time')
                            ->opens(OpenDirection::CENTER)
                            ->maxDate(Carbon::now())
                            ->autoApply(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_at'] ?? null) {
                            $createdAtFilter = $data['created_at'];
                            $indicators[] = "Click Time: {$createdAtFilter}";
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_at'] ?? null,
                            function (Builder $query, string $createdAtFilter): Builder {
                                $createdAtFilterParts = explode(' - ', $createdAtFilter);

                                $clickTimeStart = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[0])
                                    ->startOfDay();
                                $clickTimeEnd = Carbon::createFromFormat('d/m/Y', $createdAtFilterParts[1])
                                    ->endOfDay();

                                return $query->whereBetween('created_at', [$clickTimeStart, $clickTimeEnd]);
                            }
                        ),
                    ),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(ClickExporter::class)
                    ->label('Export selected'),

                DeleteBulkAction::make(),

                ExportAction::make()
                    ->exporter(ClickExporter::class)
                    ->label('Export all'),
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
            'index' => ListClicks::route('/'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['conversions', 'campaign.events', 'refer']);
    }
}
