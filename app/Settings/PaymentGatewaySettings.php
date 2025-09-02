<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class PaymentGatewaySettings extends Settings
{
    public string $active_payment_gateway;

    public ?string $affxpay_key = null;

    public ?string $bulkpe_auth_token = null;

    public ?string $kritarth_api_id = null;

    public ?string $kritarth_secret_key = null;

    public ?string $openmoney_virtual_fund_account_id = null;

    public ?string $openmoney_access_key = null;

    public ?string $openmoney_secret_key = null;

    public static function group(): string
    {
        return 'payment_gateway';
    }
}
