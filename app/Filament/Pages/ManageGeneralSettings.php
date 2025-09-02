<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;
use UnitEnum;

final class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static ?string $title = 'General Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Site Settings')
                    ->schema([
                        TextInput::make('site_name')
                            ->required()
                            ->default(config('app.name'))
                            ->maxLength(255),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('contact_telegram')
                            ->label('Contact Telegram Link')
                            ->url()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
