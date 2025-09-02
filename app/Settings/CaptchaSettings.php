<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class CaptchaSettings extends Settings
{
    public bool $enable_captcha_in_campaign_form;

    public bool $enable_captcha_in_refer_form;

    public bool $enable_captcha_in_tracker_page;

    public bool $show_badge;

    public string $site_key;

    public string $secret_key;

    public static function group(): string
    {
        return 'captcha';
    }
}
