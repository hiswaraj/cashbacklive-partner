<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\AccessPolicy;
use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Refer;
use App\Rules\UPI;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use UnitEnum;

final class ManualRefer extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?array $results = null;

    protected string $view = 'filament.pages.manual-refer';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|UnitEnum|null $navigationGroup = 'Miscellaneous';

    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make('Referrer Details')
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->options(
                                Campaign::where('access_policy', '!=', AccessPolicy::PRIVATE->value)
                                    ->where('referral_policy', '!=', ReferralPolicy::DISABLED->value)
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('commissionSplits', []); // Reset on change
                                if ($state === null) {
                                    return;
                                }

                                $campaign = Campaign::find($state);
                                if (! $campaign) {
                                    return;
                                }

                                $splittableEvents = $campaign->events()
                                    ->where('is_commission_split_allowed', true)
                                    ->get();

                                $defaults = [];
                                foreach ($splittableEvents as $event) {
                                    $min = $event->min_refer_commission;
                                    $max = $event->max_refer_commission;
                                    $defaults[$event->id] = min($max, max($min, $event->refer_amount));
                                }
                                $set('commissionSplits', $defaults);
                                $this->results = null; // Clear results when campaign changes
                            }),

                        TextInput::make('upi')
                            ->label('UPI ID')
                            ->required()
                            ->rules([new UPI()]),

                        TextInput::make('mobile')
                            ->label('Mobile Number')
                            ->required()
                            ->tel()
                            ->regex('/^[6-9]\d{9}$/'),

                        TextInput::make('telegram_url')
                            ->label('Telegram URL (Optional)')
                            ->url()
                            ->nullable(),
                    ])->columns(2),

                Section::make('Commission Split Settings')
                    ->description('Customize referral commission. These settings only apply if the selected campaign has events with commission splitting enabled.')
                    ->schema(function (Get $get): array {
                        $campaignId = $get('campaign_id');
                        if (! $campaignId) {
                            return [];
                        }

                        $campaign = Campaign::with(['events' => fn ($query) => $query->where('is_commission_split_allowed', true)->orderBy('sort_order')])
                            ->find($campaignId);

                        if (! $campaign || $campaign->events->isEmpty()) {
                            return [];
                        }

                        $components = [];
                        foreach ($campaign->events as $event) {
                            $totalCommission = $event->user_amount + $event->refer_amount;
                            $min = $event->min_refer_commission;
                            $max = $event->max_refer_commission;

                            $components[] = Section::make("Event: {$event->label}")
                                ->description("Total Bonus: ₹{$totalCommission}. Your share can be from ₹{$min} to ₹{$max}.")
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make("commissionSplits.{$event->id}")
                                            ->label('Referrer Share (You get)')
                                            ->required()
                                            ->numeric()
                                            ->minValue($min)
                                            ->maxValue($max)
                                            ->prefix('₹')
                                            ->live(onBlur: true),

                                        Placeholder::make("user_share_display.{$event->id}")
                                            ->label('New User Share')
                                            ->content(function (Get $get) use ($totalCommission, $event): string {
                                                $refererShare = $get("commissionSplits.{$event->id}") ?? 0;
                                                $userShare = $totalCommission - (int) $refererShare;

                                                return "₹{$userShare}";
                                            }),
                                    ]),
                                ])->collapsible()->collapsed();
                        }

                        return $components;
                    })
                    ->visible(fn (Get $get): bool => $get('campaign_id') &&
                        Event::where('campaign_id', $get('campaign_id'))
                            ->where('is_commission_split_allowed', true)
                            ->exists()),
            ]);
    }

    public function createRefer(): void
    {
        $this->results = null; // Clear previous results on new submission
        $this->form->validate();
        $data = $this->form->getState();

        $refer = Refer::updateOrCreate(
            [
                'campaign_id' => $data['campaign_id'],
                'upi' => $data['upi'],
            ],
            [
                'mobile' => $data['mobile'],
                'telegram_url' => $data['telegram_url'],
                'commission_split_settings' => $data['commissionSplits'] ?? [],
            ]
        );

        $this->results = [
            'referralLink' => route('short.campaign.show', ['campaign_or_refer_id' => $refer->id]),
            'trackerLink' => route('short.refer-tracker.report', ['refer' => $refer->id]),
        ];
    }
}
