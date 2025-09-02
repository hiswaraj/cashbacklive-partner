<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Settings\TelegramSettings;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramService
{
    private string $botToken;

    public function __construct(
        private readonly TelegramSettings $telegramSettings
    ) {
        if ($this->hasTokenConfigured()) {
            $this->botToken = $this->telegramSettings->telegram_bot_token;
        }
    }

    public function sendMessageToAdmin(string $text): void
    {
        if (! $this->hasAdminChatIdConfigured()) {
            Log::warning('Telegram chat ID(s) are not configured.');

            return;
        }

        foreach ($this->telegramSettings->telegram_chat_ids as $chatId) {
            $this->sendMessage((string) $chatId, $text);
        }
    }

    public function sendMessage(string $chatId, string $text): void
    {
        if (! $this->hasTokenConfigured()) {
            Log::warning('Telegram bot token is not configured.');

            return;
        }

        try {
            Http::get("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send Telegram notification', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
        }
    }

    private function hasTokenConfigured(): bool
    {
        return isset($this->telegramSettings->telegram_bot_token) && ($this->telegramSettings->telegram_bot_token !== '');
    }

    private function hasAdminChatIdConfigured(): bool
    {
        return isset($this->telegramSettings->telegram_chat_ids)
            && is_array($this->telegramSettings->telegram_chat_ids)
            && count($this->telegramSettings->telegram_chat_ids) > 0;
    }
}
