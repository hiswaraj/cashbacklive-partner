<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\CaptchaSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;
use UnitEnum;

final class ManageCaptchaSettings extends SettingsPage
{
    protected static string $settings = CaptchaSettings::class;

    protected static ?string $title = 'reCAPTCHA Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('reCAPTCHA Configuration')
                    ->description('Configure your Google reCAPTCHA settings here.')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('site_key')
                                    ->label('Site Key')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter your reCAPTCHA site key'),
                                TextInput::make('secret_key')
                                    ->label('Secret Key')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter your reCAPTCHA secret key'),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Form Settings')
                    ->schema([
                        Toggle::make('enable_captcha_in_campaign_form')
                            ->label('Enable in Campaign Form')
                            ->helperText('Turn on or off captcha verification for campaign form'),
                        Toggle::make('enable_captcha_in_refer_form')
                            ->label('Enable in Refer Form')
                            ->helperText('Turn on or off captcha verification for refer form'),
                        Toggle::make('enable_captcha_in_tracker_page')
                            ->label('Enable in Tracker Page')
                            ->helperText('Turn on or off captcha verification for campaign & refer tracker page'),
                        Toggle::make('show_badge')
                            ->label('Show reCAPTCHA Badge')
                            ->helperText('Turn on or off displaying reCAPTCHA badge on bottom right corner')
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
