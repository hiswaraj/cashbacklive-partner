<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGateway;

final class AffxPayGateway extends PaymentGateway
{
    private const string API_BASE_URL = 'https://affxpay.com/api';

    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function processPayment(string $upi, int $amount, string $referenceId, ?string $comment = null): array
    {
        $response = $this->getIpv4Client()
            ->get(self::API_BASE_URL.'/pay', [
                'upi' => $upi,
                'amount' => $amount,
                'comment' => $comment,
                'api' => $this->apiKey,
            ]);

        $responseData = $this->ensureArray($response->json());

        return [
            'status' => $this->extractStatusFromPayload($responseData),
            'reference_id' => $referenceId,
            'payment_id' => $responseData['trx_id'] ?? null,
            'api_response' => $response->body(),
        ];
    }

    /**
     * Parse webhook data from BulkPe
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function parseWebhookData(array $payload): array
    {
        $payloadData = $this->ensureArray($payload);

        return [
            'status' => $this->extractStatusFromPayload($payloadData),
            'payment_id' => $payloadData['trx_id'] ?? null,
        ];
    }

    public function fetchPaymentStatus(string $paymentId, string $referenceId): array
    {
        $response = $this->getIpv4Client()
            ->get(self::API_BASE_URL.'/fetch-balance', [
                'transaction_id' => $paymentId,
                'api' => $this->apiKey,
            ]);

        $responseData = $this->ensureArray($response->json());

        return [
            'status' => $this->extractStatusFromPayload($responseData),
            'api_response' => $response->body(),
        ];
    }
}
