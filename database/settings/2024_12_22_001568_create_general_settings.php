<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'CashbackLive');
        $this->migrator->add('general.contact_telegram', 'https://t.me/cashbacklive_official');
        $this->migrator->add('general.contact_email', 'support@cashbacklive.in');
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.contact_telegram');
        $this->migrator->delete('general.contact_email');
    }
};
