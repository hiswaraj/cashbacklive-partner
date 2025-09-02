<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

final class OpenMoneyGateway extends PaymentGateway
{
    private const string API_BASE_URL = 'https://api.openmoney.com/api/v1';

    public function __construct(
        private readonly string $virtualFundAccountId,
        private readonly string $accessKey,
        private readonly string $secretKey,
    ) {}

    public function processPayment(string $upi, int $amount, string $referenceId, ?string $comment = null): array
    {
        $beneficiaryId = $this->createBeneficiary($upi);
        $response = $this->getAuthenticatedClient()
            ->post(self::API_BASE_URL.'/transfers', [
                'type' => 'vpa',
                'debit_account_id' => $this->virtualFundAccountId,
                'beneficiary_id' => $beneficiaryId,
                'amount' => $amount,
                'currency_code' => 'inr',
                'merchant_reference_id' => $referenceId,
                'payment_remark' => $comment,
            ]);

        /** @var array<string, string|null> $responseData */
        $responseData = $this->ensureArray($response->json());

        return [
            'status' => $this->extractStatusFromPayload($responseData),
            'reference_id' => $referenceId,
            'payment_id' => $responseData['payment_id'] ?? null,
            'api_response' => $response->body(),
        ];
    }

    public function fetchPaymentStatus(string $paymentId, string $referenceId): array
    {
        return [
            'status' => self::STATUS_FAILED,
            'api_response' => 'openmoney: api not fully implemented',
        ];
    }

    /**
     * Parse webhook data from OpenMoney
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function parseWebhookData(array $payload): array
    {
        $payloadData = $this->ensureArray($payload['data']);

        return [
            'status' => $this->extractStatusFromPayload($payloadData),
            'payment_id' => $payloadData['payment_id'] ?? null,
        ];
    }

    private function createBeneficiary(string $upi): string
    {
        $name = $this->generateRandomName();
        $response = $this->getAuthenticatedClient()
            ->post(self::API_BASE_URL.'/accounts/'.$this->virtualFundAccountId.'/beneficiaries', [
                'type' => 'vpa',
                'name_of_account_holder' => $name,
                'vpa' => $upi,
            ]);

        /** @var array<string, string|null> $responseData */
        $responseData = $response->json();

        if (! isset($responseData['id'])) {
            throw new RuntimeException('Failed to create beneficiary: '.json_encode($responseData));
        }

        return $responseData['id'];
    }

    private function getAuthenticatedClient(): PendingRequest
    {
        $authToken = $this->accessKey.':'.$this->secretKey;

        return $this->getIpv4Client()->withToken($authToken);
    }
}
