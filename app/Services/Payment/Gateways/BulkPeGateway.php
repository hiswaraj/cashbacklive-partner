<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;

final class BulkPeGateway extends PaymentGateway
{
    private const string API_BASE_URL = 'https://api.bulkpe.in';

    public function __construct(
        private readonly string $authToken,
    ) {}

    public function processPayment(string $upi, int $amount, string $referenceId, ?string $comment = null): array
    {
        $name = $this->generateRandomName();

        $response = $this->getAuthenticatedClient()
            ->post(self::API_BASE_URL.'/client/initiatepayout', [
                'amount' => $amount,
                'account_number' => '', // Required empty field
                'payment_mode' => 'UPI',
                'reference_id' => $referenceId,
                'transcation_note' => $comment,
                'beneficiaryName' => $name,
                'ifsc' => '', // Required empty field
                'upi' => $upi,
            ]);

        $responseData = $this->ensureArray($response->json());

        /** @var array<string, string|null> $responseDataData */
        $responseDataData = $this->ensureArray($responseData['data']);

        return [
            'status' => $this->extractStatusFromPayload($responseDataData),
            'reference_id' => $referenceId,
            'payment_id' => $responseDataData['transcation_id'] ?? null,
            'api_response' => $response->body(),
        ];
    }

    public function fetchPaymentStatus(string $paymentId, string $referenceId): array
    {
        $response = $this->getAuthenticatedClient()
            ->post(self::API_BASE_URL.'/client/fetchStatus', [
                'transcation_id' => $paymentId,
                'reference_id' => $referenceId,
            ]);

        $responseData = $this->ensureArray($response->json());

        /** @var array<string, string|null> $responseDataData */
        $responseDataData = $this->ensureArray($responseData['data']);

        return [
            'status' => $this->extractStatusFromPayload($responseDataData),
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
        $payloadData = $this->ensureArray($payload['data']);

        return [
            'status' => $this->extractStatusFromPayload($payloadData, 'trx_status'),
            'payment_id' => $payloadData['transcation_id'] ?? null,
        ];
    }

    private function getAuthenticatedClient(): PendingRequest
    {
        return $this->getIpv4Client()->withToken($this->authToken);
    }
}
