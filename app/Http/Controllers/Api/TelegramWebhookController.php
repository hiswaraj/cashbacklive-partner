<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Settings\TelegramSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class TelegramWebhookController
{
    public function __construct(private TelegramSettings $telegramSetting) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $update = json_decode($request->getContent(), true);

        if (! isset($update['message'])) {
            return response()->json(['status' => 'error', 'message' => 'No message found'], 400);
        }

        $chatId = (string) $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';

        // If the message is "/start" or any other command, we'll return the chat ID
        if (str_starts_with((string) $text, '/')) {
            $responseText = "Your Chat ID is: $chatId";
            $this->sendTelegramMessage($chatId, $responseText);
        }

        return response()->json(['status' => 'success']);
    }

    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = $this->telegramSetting->telegram_bot_token;
        $url = "https://api.telegram.org/bot$botToken/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            Log::error('Failed to send Telegram message: '.error_get_last()['message']);
        }
    }
}
