<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('captcha.enable_captcha_in_campaign_form', false);
        $this->migrator->add('captcha.enable_captcha_in_refer_form', false);
        $this->migrator->add('captcha.enable_captcha_in_tracker_page', false);
        $this->migrator->add('captcha.show_badge', true);
        $this->migrator->add('captcha.site_key', '');
        $this->migrator->add('captcha.secret_key', '');
    }

    public function down(): void
    {
        $this->migrator->delete('captcha.enable_captcha_in_campaign_form');
        $this->migrator->delete('captcha.enable_captcha_in_refer_form');
        $this->migrator->delete('captcha.enable_captcha_in_tracker_page');
        $this->migrator->delete('captcha.show_badge');
        $this->migrator->delete('captcha.site_key');
        $this->migrator->delete('captcha.secret_key');
    }
};
