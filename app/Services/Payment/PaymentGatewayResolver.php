<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Gateways\AffxPayGateway;
use App\Services\Payment\Gateways\BulkPeGateway;
use App\Services\Payment\Gateways\KritarthGateway;
use App\Services\Payment\Gateways\OpenMoneyGateway;
use App\Settings\PaymentGatewaySettings;
use InvalidArgumentException;

final readonly class PaymentGatewayResolver
{
    public function __construct(
        private PaymentGatewaySettings $settings
    ) {}

    public function resolveByName(string $gatewayName): ?PaymentGateway
    {
        return match ($gatewayName) {
            'affxpay' => new AffxPayGateway(
                $this->settings->affxpay_key ?? ''
            ),
            'bulkpe' => new BulkPeGateway(
                $this->settings->bulkpe_auth_token ?? ''
            ),
            'kritarth' => new KritarthGateway(
                $this->settings->kritarth_secret_key ?? '',
                $this->settings->kritarth_api_id ?? ''
            ),
            'openmoney' => new OpenMoneyGateway(
                $this->settings->openmoney_virtual_fund_account_id ?? '',
                $this->settings->openmoney_access_key ?? '',
                $this->settings->openmoney_secret_key ?? ''
            ),
            default => null,
        };
    }

    public function resolveActive(): PaymentGateway
    {
        $gateway = $this->resolveByName($this->settings->active_payment_gateway);

        if (! $gateway instanceof PaymentGateway) {
            throw new InvalidArgumentException(
                "Payment gateway [{$this->settings->active_payment_gateway}] not configured."
            );
        }

        return $gateway;
    }
}
