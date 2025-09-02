<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('telegram.telegram_bot_token', '');
        $this->migrator->add('telegram.telegram_chat_ids', []);
    }

    public function down(): void
    {
        $this->migrator->delete('telegram.telegram_bot_token');
        $this->migrator->delete('telegram.telegram_chat_ids');
    }
};
