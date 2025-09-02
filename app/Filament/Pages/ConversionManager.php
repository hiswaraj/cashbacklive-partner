<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Event;
use App\Models\UPIBlockList;
use App\Services\Payment\PaymentService;
use BackedEnum;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

final class ConversionManager extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ?array $data = [];

    public array $eventOptions = [];

    public array $tableData = [];

    protected string $view = 'filament.pages.conversion-manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Miscellaneous';

    protected static ?int $navigationSort = 2;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('campaign_ids')
                                    ->label('Campaigns')
                                    ->options(Campaign::pluck('name', 'id'))
                                    ->required()
                                    ->multiple()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, ?array $state): void {
                                        $set('event_ids', []);
                                        $set('eventPaymentSettings', []);
                                        if (empty($state)) {
                                            $this->eventOptions = [];

                                            return;
                                        }
                                        $this->eventOptions = Event::whereIn('campaign_id', $state)
                                            ->with('campaign:id,name')
                                            ->orderBy('sort_order')
                                            ->get()
                                            ->mapWithKeys(function (Event $event) {
                                                $label = "{$event->param} ({$event->campaign->name})";

                                                return [$event->id => $label];
                                            })
                                            ->unique()
                                            ->toArray();
                                    }),

                                Select::make('event_ids')
                                    ->label('Events')
                                    ->options(fn (): array => $this->eventOptions)
                                    ->required()
                                    ->multiple()
                                    ->preload()
                                    ->live()
                                    ->visible(fn (callable $get): bool => ! empty($get('campaign_ids')))
                                    ->afterStateUpdated(function (callable $get, callable $set, ?array $state): void {
                                        $state = $state ?? [];
                                        $currentSettings = $get('eventPaymentSettings') ?? [];
                                        $newSettings = [];

                                        // Filter out deselected events, keeping user changes
                                        foreach ($currentSettings as $eventId => $settings) {
                                            if (in_array($eventId, $state, true)) {
                                                $newSettings[$eventId] = $settings;
                                            }
                                        }

                                        // Add new events with defaults
                                        $newlySelectedIds = array_diff($state, array_keys($newSettings));
                                        if (! empty($newlySelectedIds)) {
                                            // Use whereIn for clarity and static analysis compatibility
                                            $newEvents = Event::whereIn('id', $newlySelectedIds)->get();
                                            foreach ($newEvents as $event) {
                                                $newSettings[$event->id] = [
                                                    'pay_user' => $event->is_instant_pay_user,
                                                    'pay_referrer' => $event->is_instant_pay_refer,
                                                ];
                                            }
                                        }
                                        $set('eventPaymentSettings', $newSettings);
                                    }),
                            ]),

                        Textarea::make('import_data')
                            ->label('Import Data (one per line)')
                            ->required()
                            ->rows(10)
                            ->placeholder('Enter data, one record per line. Format: click_id,is_valid,reason
Example 1: clk_123,true,User completed action
Example 2: clk_456,false,Duplicate entry
Example 3: clk_789 (will default to valid)'),

                        Section::make('Payment Settings')
                            ->description('Control payment processing for each selected event. The initial state is based on the event\'s default settings.')
                            ->schema(function (callable $get): array {
                                $eventIds = $get('event_ids');
                                if (empty($eventIds)) {
                                    return [];
                                }

                                $events = Event::with('campaign:id,name')
                                    ->whereIn('id', $eventIds)
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->keyBy('id');

                                $components = [];
                                // Loop through selected IDs to maintain order
                                foreach ($eventIds as $eventId) {
                                    if (! isset($events[$eventId])) {
                                        continue;
                                    }
                                    $event = $events[$eventId];
                                    $components[] = Section::make()
                                        ->schema([
                                            TextEntry::make("event_label_{$event->id}")
                                                ->label("{$event->param} ({$event->campaign->name})")
                                                ->state('')
                                                ->columnSpan(1),
                                            Toggle::make("eventPaymentSettings.{$event->id}.pay_user")
                                                ->label('Pay User')
                                                ->columnSpan(1),
                                            Toggle::make("eventPaymentSettings.{$event->id}.pay_referrer")
                                                ->label('Pay Referrer')
                                                ->columnSpan(1),
                                        ])->columns(3);
                                }

                                return $components;
                            })
                            ->columns(1)
                            ->dense()
                            ->visible(fn (callable $get): bool => ! empty($get('event_ids'))),
                    ]),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getFilteredTableData())
            ->columns([
                TextColumn::make('click_id')
                    ->label('Click ID')
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('messages')
                    ->label('Messages')
                    ->bulleted()
                    ->listWithLineBreaks()
                    ->wrap(),
            ])
            ->paginated(false)
            ->deferLoading();
    }

    public function process(): void
    {
        $this->form->validate();
        $data = $this->form->getState();

        $importData = collect(explode("\n", (string) ($data['import_data'] ?? '')))
            ->map(fn (string $line): array => str_getcsv($line))
            ->filter(fn (array $row): bool => ! empty($row[0]));

        $eventIds = $data['event_ids'] ?? [];
        $paymentSettings = $data['eventPaymentSettings'] ?? [];
        $results = [];

        $events = Event::whereIn('id', $eventIds)->get()->keyBy('id');

        foreach ($importData as $row) {
            $clickId = mb_trim($row[0]);
            // Interpret 'false' or '0' as false, everything else (including empty) as true.
            $isValid = isset($row[1]) ? ! in_array(mb_strtolower(mb_trim($row[1])), ['false', '0'], true) : true;
            $reason = isset($row[2]) ? mb_trim($row[2]) : null;

            foreach ($eventIds as $eventId) {
                $event = $events->get($eventId);
                if (! $event) {
                    continue;
                }

                $result = $this->processRecord($clickId, $event, $isValid, $reason, $paymentSettings);
                $results[] = [
                    'click_id' => $clickId,
                    'event' => $event->param,
                    'status' => $result['status'],
                    'messages' => explode("\n", $result['message']),
                ];
            }
        }

        $this->tableData = $results;
        $this->resetTable();
    }

    private function getFilteredTableData(): Collection
    {
        $data = collect($this->tableData);
        $search = $this->getTableSearch();

        if (filled($search)) {
            $data = $data->filter(function ($record) use ($search): bool {
                return Str::contains((string) ($record['click_id'] ?? ''), $search, true) ||
                    Str::contains((string) ($record['event'] ?? ''), $search, true);
            });
        }

        return $data;
    }

    /**
     * @param  array<int, array<string, bool>>  $paymentSettings
     * @return array<string, string>
     */
    private function processRecord(string $clickId, Event $event, bool $isValid, ?string $reason, array $paymentSettings): array
    {
        try {
            return DB::transaction(function () use ($clickId, $event, $isValid, $reason, $paymentSettings) {
                $click = Click::with(['campaign', 'refer'])->find($clickId);
                if (! $click) {
                    return ['status' => 'error', 'message' => 'Click ID not found'];
                }

                if ($click->campaign_id !== $event->campaign_id) {
                    return ['status' => 'error', 'message' => "Event '{$event->param}' does not belong to click's campaign '{$click->campaign->name}'"];
                }

                /** @var Conversion|null $conversion */
                $conversion = Conversion::where('click_id', $click->id)
                    ->where('event_id', $event->id)
                    ->first();

                if ($conversion) {
                    return $this->updateConversion($conversion, $isValid, $reason, $paymentSettings);
                }

                return $this->createConversion($click, $event, $isValid, $reason, $paymentSettings);
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<int, array<string, bool>>  $paymentSettings
     * @return array<string, string>
     */
    private function createConversion(Click $click, Event $event, bool $wantsToBeValid, ?string $reason, array $paymentSettings): array
    {
        if (! $wantsToBeValid) {
            Conversion::create([
                'click_id' => $click->id,
                'event_id' => $event->id,
                'is_valid' => false,
                'reason' => $reason ?? 'Marked as invalid by admin',
                'ip_address' => '0.0.0.0',
            ]);

            return ['status' => 'success', 'message' => 'Created as invalid.'];
        }

        // Run system validations before creating a valid conversion
        $validationData = $this->validateConversion($click, $event);
        $conversion = Conversion::create($validationData);

        if (! $validationData['is_valid']) {
            return ['status' => 'success', 'message' => 'Created as invalid: '.($validationData['reason'] ?? 'System validation failed.')];
        }

        // Process payments if valid
        $forceInstantPayUser = $paymentSettings[$event->id]['pay_user'] ?? false;
        $forceInstantPayReferrer = $paymentSettings[$event->id]['pay_referrer'] ?? false;

        // The service now handles creating earnings and initiating payouts based on the force flags.
        app(PaymentService::class)->processEarningsForConversion(
            conversion: $conversion,
            forceInstantPayUser: $forceInstantPayUser,
            forceInstantPayReferrer: $forceInstantPayReferrer
        );

        return ['status' => 'success', 'message' => 'Created as valid and earnings processed.'];
    }

    /**
     * @param  array<int, array<string, bool>>  $paymentSettings
     * @return array<string, string>
     */
    private function updateConversion(Conversion $conversion, bool $isValid, ?string $reason, array $paymentSettings): array
    {
        if ($conversion->is_valid === $isValid) {
            return ['status' => 'info', 'message' => 'No change needed.'];
        }

        if ($isValid) { // Mark as valid
            $conversion->is_valid = true;
            $conversion->reason = null;
            $conversion->save();

            $event = $conversion->event;
            $forceInstantPayUser = $paymentSettings[$event->id]['pay_user'] ?? false;
            $forceInstantPayReferrer = $paymentSettings[$event->id]['pay_referrer'] ?? false;

            app(PaymentService::class)->processEarningsForConversion(
                conversion: $conversion,
                forceInstantPayUser: $forceInstantPayUser,
                forceInstantPayReferrer: $forceInstantPayReferrer
            );

            return ['status' => 'success', 'message' => 'Updated to valid and earnings created.'];
        }

        // Mark as invalid
        if ($conversion->earnings()->whereNotNull('payout_id')->exists()) {
            return ['status' => 'error', 'message' => 'Cannot invalidate, earnings are part of a payout.'];
        }

        // Delete all associated earnings.
        $conversion->earnings()->delete();

        $conversion->is_valid = false;
        $conversion->reason = $reason ?? 'Marked as invalid by admin';
        $conversion->save();

        return ['status' => 'success', 'message' => 'Updated to invalid and earnings deleted.'];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateConversion(Click $click, Event $event): array
    {
        $conversionData = [
            'click_id' => $click->id,
            'event_id' => $event->id,
            'is_valid' => true,
            'ip_address' => '0.0.0.0', // Manual postbacks don't have a real IP
        ];

        if ($this->conversionExists($click, $event)) {
            return $this->invalidConversionData($conversionData, 'Conversion already exists');
        }

        if (UPIBlockList::isUpiBlocked($click->upi)) {
            return $this->invalidConversionData($conversionData, 'UPI is blocked');
        }

        if ($this->hasExceededUpiAttempts($click)) {
            return $this->invalidConversionData($conversionData, 'Maximum UPI attempts reached');
        }

        if ($this->hasExceededIpAttempts($click)) {
            return $this->invalidConversionData($conversionData, 'Maximum IP attempts reached');
        }

        return $conversionData;
    }

    private function conversionExists(Click $click, Event $event): bool
    {
        return Conversion::where('click_id', $click->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $conversionData
     * @return array<string, mixed>
     */
    private function invalidConversionData(array $conversionData, string $reason): array
    {
        $conversionData['is_valid'] = false;
        $conversionData['reason'] = $reason;

        return $conversionData;
    }

    private function hasExceededUpiAttempts(Click $click): bool
    {
        if ($click->campaign->is_direct_redirect) {
            return false;
        }

        $upiAttempts = Click::where('campaign_id', $click->campaign_id)
            ->where('upi', $click->upi)
            ->count();

        return $upiAttempts > $click->campaign->max_upi_attempts;
    }

    private function hasExceededIpAttempts(Click $click): bool
    {
        $ipAttempts = Click::where('campaign_id', $click->campaign_id)
            ->where('ip_address', $click->ip_address)
            ->count();

        return $ipAttempts > $click->campaign->max_ip_attempts;
    }
}
