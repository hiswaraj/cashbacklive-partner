<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\PaymentGatewaySettings;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Override;
use UnitEnum;

final class ManagePaymentGatewaySettings extends SettingsPage
{
    protected static string $settings = PaymentGatewaySettings::class;

    protected static ?string $title = 'Payment Gateway Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('active_payment_gateway')
                    ->options([
                        'affxpay' => 'AffxPay',
                        'bulkpe' => 'BulkPe',
                        'kritarth' => 'Kritarth',
                        'openmoney' => 'OpenMoney',
                    ])
                    ->required()
                    ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                        if ($state === null || $state === '') {
                            $set('webhook_url', 'Please select a payment gateway first');

                            return;
                        }
                        $set('webhook_url', route('webhook.payment-status', ['gateway' => $state]));
                    })
                    ->live(),

                Placeholder::make('Note*')
                    ->content('Remember to whitelist this server\'s IP address in your payment gateway panel. Current IP: '.$this->getPublicIPv4())
                    ->extraAttributes(['class' => 'text-sm text-gray-600']),

                Section::make('AffxPay Settings')
                    ->schema([
                        TextInput::make('affxpay_key')
                            ->label('API Key')
                            ->requiredIf('active_payment_gateway', 'affxpay'),
                    ])
                    ->collapsible()
                    ->visible(fn (callable $get): bool => $get('active_payment_gateway') === 'affxpay')
                    ->columnSpanFull(),

                Section::make('BulkPe Settings')
                    ->schema([
                        TextInput::make('bulkpe_auth_token')
                            ->label('Auth Token')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'bulkpe'),
                    ])
                    ->collapsible()
                    ->visible(fn (callable $get): bool => $get('active_payment_gateway') === 'bulkpe')
                    ->columnSpanFull(),

                Section::make('Kritarth Settings')
                    ->schema([
                        TextInput::make('kritarth_secret_key')
                            ->label('Secret Key')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'kritarth'),
                        TextInput::make('kritarth_api_id')
                            ->label('API ID')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'kritarth'),
                    ])
                    ->collapsible()
                    ->visible(fn (callable $get): bool => $get('active_payment_gateway') === 'kritarth')
                    ->columnSpanFull(),

                Section::make('OpenMoney Settings')
                    ->schema([
                        TextInput::make('openmoney_virtual_fund_account_id')
                            ->label('Virtual Fund Account ID')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'openmoney'),
                        TextInput::make('openmoney_access_key')
                            ->label('Access Key')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'openmoney'),
                        TextInput::make('openmoney_secret_key')
                            ->label('Secret Key')
                            ->maxLength(255)
                            ->requiredIf('active_payment_gateway', 'openmoney'),
                    ])
                    ->collapsible()
                    ->visible(fn (callable $get): bool => $get('active_payment_gateway') === 'openmoney')
                    ->columnSpanFull(),

                Section::make('Webhook Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Placeholder::make('webhook_note')
                                    ->content('Configure this webhook URL in your payment gateway dashboard to receive payment status updates automatically.')
                                    ->extraAttributes(['class' => 'text-sm text-gray-600']),
                                TextInput::make('webhook_url')
                                    ->readOnly()
                                    ->label('Webhook URL')
                                    ->afterStateHydrated(function (TextInput $component, ?string $state, callable $get): void {
                                        $gateway = $get('active_payment_gateway');
                                        if (! empty($gateway)) {
                                            $component->state(route('webhook.payment-status', ['gateway' => $gateway]));
                                        } else {
                                            $component->state('Please select a payment gateway first');
                                        }
                                    })
                                    ->suffixAction(
                                        Action::make('copy')
                                            ->icon('heroicon-s-clipboard')
                                            ->action(function (Component $livewire, ?string $state): void {
                                                $livewire->dispatch('copy-to-clipboard', text: $state);
                                            })
                                    )
                                    ->extraAttributes([
                                        'x-data' => "{
                                            copyToClipboard(text) {
                                                navigator.clipboard.writeText(text)
                                                \$tooltip('Copied to clipboard', { timeout: 1500 });
                                            },
                                        }",
                                        'x-on:copy-to-clipboard.window' => 'copyToClipboard($event.detail.text)',
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function getPublicIPv4(): string
    {
        try {
            $response = Http::get('https://api.ipify.org');
            if ($response->successful()) {
                return $response->body();
            }
        }
        // FIXME: codeCoverageIgnore
        // @codeCoverageIgnoreStart
        catch (Exception $e) {
            logger()->error('Unable to determine public IP address: '.$e->getMessage());
        }

        return 'Unable to determine public IP address';
        // @codeCoverageIgnoreEnd
    }
}
