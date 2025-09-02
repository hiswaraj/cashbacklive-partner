<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGateway;

final class KritarthGateway extends PaymentGateway
{
    private const string API_BASE_URL = 'https://dashboard.kritarthtechnologies.com/KRI/v2';

    public function __construct(
        private readonly string $secretKey,
        private readonly string $apiId,
    ) {}

    public function processPayment(string $upi, int $amount, string $referenceId, ?string $comment = null): array
    {
        $name = $this->generateRandomName();

        $response = $this->getIpv4Client()->get(self::API_BASE_URL, [
            'secret_key' => $this->secretKey,
            'api_id' => $this->apiId,
            'name' => $name,
            'upi' => $upi,
            'amount' => number_format($amount, 2, '.', ''),
            'order_id' => $referenceId,
            'comment' => $comment,
        ]);

        $responseData = $this->ensureArray($response->json());

        return [
            'status' => $this->extractStatusFromPayload($responseData),
            'reference_id' => $referenceId,
            'payment_id' => $responseData['tnx_id'] ?? null,
            'api_response' => $response->body(),
        ];
    }

    public function fetchPaymentStatus(string $paymentId, string $referenceId): array
    {
        $params = [
            'secret_key' => $this->secretKey,
            'api_id' => $this->apiId,
            'order_id' => $referenceId,
        ];

        $response = $this->getIpv4Client()->get(self::API_BASE_URL.'/tnx_status/', $params);

        $responseData = $this->ensureArray($response->json());

        return [
            'status' => $this->extractStatusFromPayload($responseData),
            'api_response' => $response->body(),
        ];
    }

    /**
     * Parse webhook data from Kritarth
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function parseWebhookData(array $payload): array
    {
        $payloadData = $this->ensureArray($payload['data']);

        return [
            'status' => $this->extractStatusFromPayload($payloadData),
            'payment_id' => $payloadData['tnx_id'] ?? null,
        ];
    }
}
