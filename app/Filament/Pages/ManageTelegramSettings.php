<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\TelegramSettings;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Override;
use UnitEnum;

final class ManageTelegramSettings extends SettingsPage
{
    protected static string $settings = TelegramSettings::class;

    protected static ?string $title = 'Telegram Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    private readonly TelegramSettings $telegramSetting;

    private string|HtmlString $botInstructions = 'Save your bot token and "Set Webhook" first to see instructions for getting a chat ID.';

    public function __construct()
    {
        $this->telegramSetting = app(TelegramSettings::class);
    }

    #[Override]
    public function mount(): void
    {
        parent::mount();
        $this->loadBotUsername();
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bot Configuration')
                    ->description('Telegram bot credentials and chat settings.')
                    ->icon('heroicon-o-key')
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('telegram_bot_token')
                            ->label('Bot Token')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Create a bot with @BotFather on Telegram to get this token.')
                            ->suffixIcon('heroicon-m-key')
                            ->mutateDehydratedStateUsing(fn (string $state): string => $this->trimBotPrefix($state))
                            ->columnSpan(['md' => 2]),

                        TagsInput::make('telegram_chat_ids')
                            ->label('Chat IDs')
                            ->helperText('The ID of each chat where notifications will be sent. Press enter to add an ID.')
                            ->placeholder('Enter Chat IDs and press enter')
                            ->rules([
                                'array',
                            ])
                            ->validationMessages([
                                'array' => 'Chat IDs must be provided as a list.',
                            ])
                            ->suffixIcon('heroicon-m-chat-bubble-oval-left-ellipsis')
                            ->columnSpan(['md' => 2]),

                        Placeholder::make('bot_instructions')
                            ->label('How to get a Chat ID')
                            ->content(fn (): string|HtmlString => $this->botInstructions),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setWebhook')
                ->label('Set Webhook')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->tooltip('Set webhook URL for your bot')
                ->action(fn () => $this->setWebhook()),

            Action::make('checkWebhook')
                ->label('Check Webhook')
                ->color('info')
                ->icon('heroicon-o-check-circle')
                ->tooltip('Verify webhook is correctly configured')
                ->action(fn () => $this->checkWebhook()),
        ];
    }

    protected function afterSave(): void
    {
        $this->loadBotUsername();
    }

    private function loadBotUsername(): void
    {
        if (isset($this->telegramSetting->telegram_bot_token) && ($this->telegramSetting->telegram_bot_token !== '')) {
            try {
                $response = Http::timeout(5)->get(
                    "https://api.telegram.org/bot{$this->telegramSetting->telegram_bot_token}/getMe"
                );

                if ($response->successful() && $response->json('ok')) {
                    $botUsername = $response->json('result.username');
                    $this->botInstructions = new HtmlString("
                        <div class='space-y-2 text-sm'>
                            <p>Any user who wants to receive notifications must complete the following steps:</p>
                            <ol class='list-decimal list-inside space-y-1'>
                                <li>
                                    Click this link to open your bot in Telegram:
                                    <a href='https://t.me/{$botUsername}' target='_blank' class='text-primary-600 hover:text-primary-500'>
                                        @{$botUsername}
                                    </a>
                                </li>
                                <li>Start a conversation with the bot by clicking \"Start\" or sending \"/start\".</li>
                                <li>The bot will reply with their unique Chat ID.</li>
                                <li>Copy the Chat ID and add it to the 'Chat IDs' field above.</li>
                            </ol>
                            <div class='mt-2 text-gray-500 dark:text-gray-400'>
                                Note: Ensure the webhook is set using the \"Set Webhook\" button before messaging the bot.
                            </div>
                        </div>
                    ");
                } else {
                    $this->botInstructions = 'Could not retrieve bot information. Please ensure the bot token is correct and try again.';
                    Notification::make()
                        ->title('Failed to load bot information')
                        ->body($response->json('description', 'An unknown error occurred.'))
                        ->danger()
                        ->send();
                }
            } catch (Exception) {
                $this->botInstructions = 'Failed to connect to Telegram. Please check your network connection and bot token.';
                Notification::make()
                    ->title('Failed to connect to Telegram')
                    ->danger()
                    ->send();
            }
        }
    }

    private function trimBotPrefix(string $token): string
    {
        return str_starts_with($token, 'bot') ? mb_substr($token, 3) : $token;
    }

    private function setWebhook(): void
    {
        $webhookUrl = route('webhook.telegram');
        $response = Http::get("https://api.telegram.org/bot{$this->telegramSetting->telegram_bot_token}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        if ($response->successful() && $response->json('ok')) {
            Notification::make()
                ->title('Webhook set successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to set webhook')
                ->body($response->json('description', 'An unknown error occurred.'))
                ->danger()
                ->send();
        }
    }

    private function checkWebhook(): void
    {
        $response = Http::get("https://api.telegram.org/bot{$this->telegramSetting->telegram_bot_token}/getWebhookInfo");

        if ($response->successful()) {
            /** @var array{url?: string} $webhookInfo */
            $webhookInfo = $response->json('result');
            $currentWebhook = $webhookInfo['url'] ?? '';
            $appWebhook = route('webhook.telegram');

            if ($currentWebhook === $appWebhook) {
                Notification::make()
                    ->title('Webhook is correctly set')
                    ->body("The webhook is set to: {$currentWebhook}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Webhook mismatch')
                    ->body("The webhook is currently set to '{$currentWebhook}'.")
                    ->danger()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Failed to check webhook')
                ->body($response->json('description', 'An unknown error occurred.'))
                ->danger()
                ->send();
        }
    }
}
