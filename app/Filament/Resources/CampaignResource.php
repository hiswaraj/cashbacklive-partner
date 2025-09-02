<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\AccessPolicy;
use App\Enums\ExtraInputType;
use App\Enums\ReferralPolicy;
use App\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use App\Filament\Resources\CampaignResource\Pages\EditCampaign;
use App\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use App\Forms\Components\TinyMCE;
use App\Models\Campaign;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Override;

final class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Campaign Details')->tabs([

                // =================================================================
                // GENERAL TAB
                // Core content and information.
                // =================================================================
                Tabs\Tab::make('General')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Section::make('Identity')
                            ->description('Core identifying information for the campaign.')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(32)
                                        ->columnSpan(2),

                                    FileUpload::make('logo_path')
                                        ->label('Logo')
                                        ->image()
                                        ->disk('public')
                                        ->imageEditor()
                                        ->circleCropper()
                                        ->imageCropAspectRatio('1:1')
                                        ->nullable()
                                        ->columnSpan(1),
                                ]),

                                TextInput::make('subtitle')
                                    ->required()
                                    ->maxLength(128)
                                    ->helperText('Use {user_amount} to display the total user payout.'),

                                TextInput::make('url')
                                    ->label('Campaign URL')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Use the `{click_id}` placeholder to have it replaced with the generated click ID.'),
                            ]),

                        Section::make('Content')
                            ->schema([
                                TinyMCE::make('description')
                                    ->required()
                                    ->placeholder('Enter the campaign description here...')
                                    ->maxLength(32767),

                                TinyMCE::make('terms')
                                    ->required()
                                    ->maxLength(32767)
                                    ->default('<p>🔸 Offer Eligibility<br>Each campaign is valid only once per user, number, UPI ID, IP address &amp; device. Multiple registrations from the same details or device are strictly prohibited.</p>
<p>&nbsp;</p>
<p>🔸 Use of Genuine Details<br>Use only real Indian mobile numbers, valid UPI IDs, and authentic email IDs. Fake, temporary, or duplicate data is not allowed.</p>
<p>&nbsp;</p>
<p>🔸 Task Completion Required<br>To receive rewards users must complete all required tasks (like app install, transaction, survey, etc.) honestly. Incomplete or manipulated activity will be marked invalid.</p>
<p>&nbsp;</p>
<p>🔸 No Duplicate or Multiple Accounts<br>Creating multiple accounts or attempting the same offer from the same device, IP, or user identity is strictly against policy. Such users will be blacklisted and denied rewards.</p>
<p>&nbsp;</p>
<p>🔸 Merchant/App Verification Mandatory<br>Rewards are processed only after merchant or brand verification. If suspicious or invalid activity is found, Reward will be denied without notice.</p>
<p>&nbsp;</p>
<p>🔸 User Data &amp; Privacy<br>By using CashbackLive, you consent to share mobile number, UPI ID, email, and device data for tracking and reward processing. We do not misuse or sell your data. In case of fraud, data may be shared with partners or legal agencies.</p>
<p>&nbsp;</p>
<p>🔸 🚨 Fraud = Legal Action<br>Use of bots, hacks, emulators, or exploiting system loopholes is strictly prohibited. Fraud may result in permanent ban and legal action under IT Act 2000 and IPC Sections 420, 468, 66C, 66D.</p>
<p>&nbsp;</p>
<p>🔸 Right to Cancel<br>CashbackLive reserves the right to cancel or deny any reward in case of policy violation or technical issues.</p>'),
                            ]),
                    ]),

                // =================================================================
                // CONFIGURATION TAB
                // All the toggles and behavioural settings.
                // =================================================================
                Tabs\Tab::make('Configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Core Settings ')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->default(true)
                                        ->helperText('Master switch for the campaign processing engine.')
                                        ->hintAction(
                                            Action::make('is_active_info')
                                                ->icon('heroicon-o-question-mark-circle')
                                                ->label('')
                                                ->modalContent(view('filament.tooltips.is-active-info'))
                                                ->modalHeading('Is Active')
                                                ->modalSubmitAction(false)
                                                ->modalCancelAction(false)
                                        ),

                                    Select::make('access_policy')
                                        ->required()
                                        ->options(AccessPolicy::class)
                                        ->default(AccessPolicy::PRIVATE)
                                        ->helperText('Controls who can see and access the campaign.')
                                        ->hintAction(
                                            Action::make('access_policy_info')
                                                ->icon('heroicon-o-question-mark-circle')
                                                ->label('')
                                                ->modalContent(view('filament.tooltips.access-policy-info'))
                                                ->modalHeading('Access Policy')
                                                ->modalSubmitAction(false)
                                                ->modalCancelAction(false)
                                        ),
                                ])->columnSpan(1),

                            Section::make('Referral Settings')
                                ->schema([
                                    Select::make('referral_policy')
                                        ->required()
                                        ->options(ReferralPolicy::class)
                                        ->default(ReferralPolicy::DISABLED)
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($get('access_policy') === AccessPolicy::REFERRAL_ONLY->value && $value === ReferralPolicy::DISABLED->value) {
                                                    $fail('A "Referral Only" campaign cannot have referrals disabled.');
                                                }
                                            },
                                        ])
                                        ->helperText('Controls the referral system behavior.')
                                        ->hintAction(
                                            Action::make('referral_policy_info')
                                                ->icon('heroicon-o-question-mark-circle')
                                                ->label('')
                                                ->modalContent(view('filament.tooltips.referral-policy-info'))
                                                ->modalHeading('Referral Policy')
                                                ->modalSubmitAction(false)
                                                ->modalCancelAction(false)
                                        ),

                                    Toggle::make('is_referer_telegram_allowed')
                                        ->label('Allow Referrer Telegram')
                                        ->default(true)
                                        ->helperText('Enable referer telegram link in the form.'),
                                ])->columnSpan(1),

                            Section::make('Telegram Settings')
                                ->schema([
                                    Toggle::make('is_footer_telegram_enabled')
                                        ->label('Show Footer Telegram')
                                        ->default(true)
                                        ->helperText('Show a Telegram link in the campaign footer.'),

                                    Toggle::make('is_telegram_enabled_on_404')
                                        ->label('Show Telegram')
                                        ->default(false)
                                        ->helperText('Show a Telegram link on the inactive(404) page.'),

                                    Toggle::make('is_auto_redirect_to_telegram_on_404')
                                        ->label('Auto Redirect')
                                        ->default(true)
                                        ->helperText('Auto redirect to Telegram when campaign is inactive(404) page.'),
                                ])->columnSpan(1),

                            Section::make('Redirection Settings')
                                ->schema([
                                    Toggle::make('is_direct_redirect')
                                        ->default(false)
                                        ->helperText('Skip form and redirect directly to campaign URL.')
                                        ->live(),
                                ])->columnSpan(1),
                        ]),
                    ]),

                // =================================================================
                // EVENTS & PAYOUTS TAB
                // Dedicated tab for the complex events repeater.
                // =================================================================
                Tabs\Tab::make('Events & Payouts')
                    ->icon('heroicon-o-currency-dollar')
                    ->badge(fn (Get $get) => is_array($get('events')) ? count($get('events')) : 0)
                    ->schema([
                        Repeater::make('events')
                            ->relationship()
                            ->schema([
                                TextInput::make('label')->required()->maxLength(32),

                                TextInput::make('param')->required()->maxLength(32),

                                Section::make('User Payment Settings')
                                    ->schema([
                                        TextInput::make('user_amount')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->prefix('₹')
                                            ->columnSpan(1)
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                // Calculate the new total based on the updated user_amount
                                                $total = (int) $state + (int) $get('refer_amount');
                                                $set('max_refer_commission', $total);

                                                // Notify only if the commission split is enabled (i.e., the field is visible)
                                                if ($get('is_commission_split_allowed')) {
                                                    Notification::make()
                                                        ->title('Max Referrer Commission auto-updated')
                                                        ->body("Set to the new total: ₹{$total}")
                                                        ->warning() // Use 'warning' to draw attention to an automatic change
                                                        ->send();
                                                }
                                            }),

                                        Section::make()->schema([
                                            Toggle::make('is_instant_pay_user')
                                                ->label('Instant Pay User')
                                                ->default(false)
                                                ->helperText('Enable instant payment for users'),
                                        ])->columnSpan(1),

                                        TextInput::make('user_payment_comment')
                                            ->maxLength(40)
                                            ->helperText('Optional comment for user payment')
                                            ->rules(['nullable', 'string', 'ascii'])
                                            ->columnSpanFull(),
                                    ])->columns(2)->collapsible(),

                                Section::make('Refer Payment Settings')
                                    ->schema([
                                        TextInput::make('refer_amount')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->prefix('₹')
                                            ->columnSpan(1)
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                // Calculate the new total based on the updated refer_amount
                                                $total = (int) $get('user_amount') + (int) $state;
                                                $set('max_refer_commission', $total);

                                                // Notify only if the commission split is enabled (i.e., the field is visible)
                                                if ($get('is_commission_split_allowed')) {
                                                    Notification::make()
                                                        ->title('Max Referrer Commission auto-updated')
                                                        ->body("Set to the new total: ₹{$total}")
                                                        ->warning()
                                                        ->send();
                                                }
                                            }),

                                        Section::make()->schema([
                                            Toggle::make('is_instant_pay_refer')
                                                ->label('Instant Pay Referrer')
                                                ->default(false)
                                                ->helperText('Enable instant payment for referrers'),
                                        ])->columnSpan(1),

                                        TextInput::make('referrer_payment_comment')
                                            ->maxLength(40)
                                            ->helperText('Optional comment for referrer payment')
                                            ->rules(['nullable', 'string', 'ascii'])
                                            ->columnSpanFull(),
                                    ])->collapsible()->columns(2),

                                Toggle::make('is_commission_split_allowed')
                                    ->label('Allow Commission Split')
                                    ->helperText('Enable to split the referrer commission.')
                                    ->default(false)
                                    ->live()
                                    ->visible(fn (Get $get): bool => ((int) $get('user_amount') + (int) $get('refer_amount')) > 0),

                                TextInput::make('min_refer_commission')
                                    ->label('Min Referrer Commission')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(fn (Get $get): int => (int) $get('user_amount') + (int) $get('refer_amount'))
                                    ->helperText('The minimum amount a referrer can take.')
                                    ->visible(fn (Get $get): bool => (bool) $get('is_commission_split_allowed')),

                                TextInput::make('max_refer_commission')
                                    ->label('Max Referrer Commission')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(fn (Get $get): int => (int) $get('user_amount') + (int) $get('refer_amount'))
                                    ->gte('min_refer_commission')
                                    ->maxValue(fn (Get $get): int => (int) $get('user_amount') + (int) $get('refer_amount'))
                                    ->helperText('The maximum amount a referrer can take.')
                                    ->visible(fn (Get $get): bool => (bool) $get('is_commission_split_allowed')),

                                TextInput::make('time_gap_in_seconds')
                                    ->label('Time Gap (Minute:Seconds)')
                                    ->placeholder('00:00')
                                    ->required()
                                    ->rules(['regex:/^([0-5]?[0-9]):([0-5][0-9])$/'])
                                    ->validationAttribute('time gap')
                                    ->dehydrateStateUsing(fn (?string $state): int => self::convertToSeconds($state))
                                    ->formatStateUsing(fn (?string $state): string => self::convertToMinutesSeconds((int) $state))
                                    ->helperText('Enter time in MM:SS format (00:00 - 59:59)'),
                            ])
                            ->hiddenLabel()
                            ->itemLabel(fn (array $state): string => $state['param'] ?? 'New Event')
                            ->orderColumn('sort_order')
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->collapsed(),
                    ]),

                // =================================================================
                // EXTRA INPUTS TAB
                // Optional custom fields to collect from the user.
                // =================================================================
                Tabs\Tab::make('Extra Inputs')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Grid::make(3)->schema([
                            Section::make('Extra Input 1')->schema([
                                Toggle::make('is_extra_input_1_active')
                                    ->label('Active')
                                    ->default(false)
                                    ->live(),
                                Toggle::make('is_extra_input_1_required')
                                    ->label('Required')
                                    ->default(false)
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_1_active')),
                                Select::make('extra_input_1_type')
                                    ->label('Type')
                                    ->options(ExtraInputType::class)
                                    ->default(ExtraInputType::MOBILE)
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_1_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_1_active')),
                                TextInput::make('extra_input_1_label')
                                    ->label('Label')
                                    ->placeholder('Mobile')
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_1_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_1_active')),
                            ]),

                            Section::make('Extra Input 2')->schema([
                                Toggle::make('is_extra_input_2_active')
                                    ->label('Active')
                                    ->default(false)
                                    ->live(),
                                Toggle::make('is_extra_input_2_required')
                                    ->label('Required')
                                    ->default(false)
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_2_active')),
                                Select::make('extra_input_2_type')
                                    ->label('Type')
                                    ->options(ExtraInputType::class)
                                    ->default(ExtraInputType::EMAIL)
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_2_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_2_active')),
                                TextInput::make('extra_input_2_label')
                                    ->label('Label')
                                    ->placeholder('Email')
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_2_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_2_active')),
                            ]),

                            Section::make('Extra Input 3')->schema([
                                Toggle::make('is_extra_input_3_active')
                                    ->label('Active')
                                    ->default(false)
                                    ->live(),
                                Toggle::make('is_extra_input_3_required')
                                    ->label('Required')
                                    ->default(false)
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_3_active')),
                                Select::make('extra_input_3_type')
                                    ->label('Type')
                                    ->options(ExtraInputType::class)
                                    ->default(ExtraInputType::NUMBER)
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_3_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_3_active')),
                                TextInput::make('extra_input_3_label')
                                    ->label('Label')
                                    ->placeholder('Bank Account Number')
                                    ->required(fn (Get $get): bool => (bool) $get('is_extra_input_3_active'))
                                    ->hidden(fn (Get $get): bool => ! $get('is_extra_input_3_active')),
                            ]),
                        ]),
                    ]),

                // =================================================================
                // SECURITY TAB
                // Rate limiting and webhook settings.
                // =================================================================
                Tabs\Tab::make('Security')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Section::make('Access Control & Webhooks')->schema([
                            TextInput::make('webhook_secret')
                                ->required()
                                ->default(Str::random(10))
                                ->helperText('Used for webhook verification.'),

                            TextInput::make('max_upi_attempts')
                                ->numeric()
                                ->minValue(1)
                                ->default(50)
                                ->required()
                                ->helperText('Maximum submission attempts per UPI ID.'),

                            TextInput::make('max_ip_attempts')
                                ->numeric()
                                ->minValue(1)
                                ->default(50)
                                ->required()
                                ->helperText('Maximum submission attempts per IP address.'),
                        ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                ImageColumn::make('logo_path')
                    ->label('Campaign Logo')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subtitle')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip(fn (?bool $state): string => $state === true ? 'Processing Engine: ON' : 'Processing Engine: OFF (Archived)'),

                TextColumn::make('access_policy')
                    ->badge()
                    ->sortable()
                    ->color(fn (AccessPolicy $state): string => match ($state) {
                        AccessPolicy::PUBLIC => 'success',
                        AccessPolicy::UNLISTED => 'warning',
                        AccessPolicy::REFERRAL_ONLY => 'info',
                        AccessPolicy::PRIVATE => 'danger',
                    }),

                TextColumn::make('referral_policy')
                    ->badge()
                    ->sortable()
                    ->color(fn (ReferralPolicy $state): string => match ($state) {
                        ReferralPolicy::OPEN => 'success',
                        ReferralPolicy::CLOSED => 'warning',
                        ReferralPolicy::DISABLED => 'danger',
                    }),

                IconColumn::make('is_referer_telegram_allowed')
                    ->boolean()
                    ->label('Referer Telegram Link')
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-link-slash')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (?bool $state): string => $state === true ? 'Referer telegram link enabled' : 'Referer telegram link disabled')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_footer_telegram_enabled')
                    ->boolean()
                    ->label('Footer Telegram Logo')
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (?bool $state): string => $state === true ? 'Footer telegram link enabled' : 'Footer telegram link disabled')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_telegram_enabled_on_404')
                    ->boolean()
                    ->label('404 Telegram')
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (?bool $state): string => $state === true ? 'Telegram link on 404 page enabled' : 'Telegram link on 404 page disabled')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_auto_redirect_to_telegram_on_404')
                    ->boolean()
                    ->label('Auto Redirect')
                    ->trueIcon('heroicon-o-arrow-trending-up')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_direct_redirect')
                    ->boolean()
                    ->label('Direct Redirect')
                    ->trueIcon('heroicon-o-arrow-right')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (?bool $state): string => $state === true ? 'Direct redirect enabled' : 'Direct redirect disabled'),

                TextColumn::make('webhook_secret')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('max_upi_attempts')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('max_ip_attempts')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),

                SelectFilter::make('access_policy')
                    ->options(AccessPolicy::class),

                SelectFilter::make('referral_policy')
                    ->options(ReferralPolicy::class),
            ])
            ->recordActions([
                Action::make('showLinks')
                    ->label('Show Links')
                    ->icon('heroicon-o-link')
                    ->modalHeading('Campaign Links')
                    ->modalContent(fn (Campaign $campaign): View => view('filament.resources.campaign-resource.show-links', [
                        'campaign' => $campaign,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Campaign $record) {
                        $newCampaign = $record->duplicate();
                        Notification::make()
                            ->title('Campaign duplicated')
                            ->success()
                            ->send();

                        return redirect()->to(CampaignResource::getUrl('edit', [$newCampaign]));
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $totalCount = Campaign::active()->count();

        if ($totalCount !== 0) {
            // FIXME: codeCoverageIgnore
            // @codeCoverageIgnoreStart
            return (string) $totalCount;
            // @codeCoverageIgnoreEnd
        }

        return null;
    }

    private static function convertToSeconds(?string $time): float|int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', (string) $time, $matches)) {
            $minutes = (int) ($matches[1]);
            $seconds = (int) ($matches[2]);

            return ($minutes * 60) + $seconds;
        }

        return 0;
    }

    private static function convertToMinutesSeconds(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}
