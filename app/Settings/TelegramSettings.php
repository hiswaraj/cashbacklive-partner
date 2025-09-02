<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class TelegramSettings extends Settings
{
    public string $telegram_bot_token;

    public array $telegram_chat_ids;

    public static function group(): string
    {
        return 'telegram';
    }
}
