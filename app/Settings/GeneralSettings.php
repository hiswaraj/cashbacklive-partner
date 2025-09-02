<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class GeneralSettings extends Settings
{
    public string $site_name;

    public string $contact_telegram;

    public string $contact_email;

    public static function group(): string
    {
        return 'general';
    }
}
