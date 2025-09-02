<?php

declare(strict_types=1);

namespace App\Services;

use App\Settings\CaptchaSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final readonly class ReCaptchaService
{
    public function __construct(
        private CaptchaSettings $settings
    ) {}

    public function verify(?string $gRecaptchaResponse): bool
    {
        if ($gRecaptchaResponse === null || $gRecaptchaResponse === '') {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $this->settings->secret_key,
                'response' => $gRecaptchaResponse,
            ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful() && $response->json('success', false);
    }
}
