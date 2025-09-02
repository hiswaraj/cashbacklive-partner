<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\ReCaptchaService;
use App\Settings\CaptchaSettings;
use Illuminate\Validation\ValidationException;

trait WithReCaptcha
{
    public ?string $gRecaptchaResponse = null;

    protected function verifyRecaptcha(): void
    {
        $settings = app(CaptchaSettings::class);

        if (! $settings->site_key) {
            return;
        }

        if (! app(ReCaptchaService::class)->verify($this->gRecaptchaResponse)) {
            throw ValidationException::withMessages([
                'gRecaptchaResponse' => 'Captcha verification failed',
            ]);
        }
    }
}
