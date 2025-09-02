<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class PaymentGateway
{
    protected const string STATUS_SUCCESS = 'success';

    protected const string STATUS_FAILED = 'failed';

    protected const string STATUS_PENDING = 'pending';

    /**
     * Process a payment through the gateway
     *
     * @return array<string, mixed>
     */
    abstract public function processPayment(
        string $upi,
        int $amount,
        string $referenceId,
        ?string $comment = null
    ): array;

    /**
     * Fetch payment status from the gateway
     *
     * @return array{status: string, api_response: string}
     */
    abstract public function fetchPaymentStatus(
        string $paymentId,
        string $referenceId
    ): array;

    /**
     * Parse webhook data from the payment gateway
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    abstract public function parseWebhookData(array $payload): array;

    protected function generateRandomName(): string
    {
        $firstNames = ['Aarav', 'Advait', 'Arjun', 'Ishaan', 'Sai', 'Vivaan', 'Ananya', 'Diya', 'Kavya', 'Prisha', 'Saanvi', 'Siya'];
        $lastNames = ['Sharma', 'Varma', 'Gupta', 'Singh', 'Kumar', 'Jain', 'Patel', 'Shah', 'Desai', 'Reddy', 'Iyer', 'Chopra'];

        return $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)];
    }

    protected function getIpv4Client(): PendingRequest
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ]);
    }

    /**
     *       Safely extracts and determines the payment status from a payload array.
     *
     * @param  array<string, mixed>  $payload  The payload array to extract the status from.
     * @param  string  $key  The key in the payload that holds the status.
     */
    protected function extractStatusFromPayload(array $payload, string $key = 'status'): string
    {
        $statusValue = $payload[$key] ?? null;

        if (! is_scalar($statusValue)) {
            return self::STATUS_FAILED;
        }

        $statusString = (string) $statusValue;

        if ($statusString === '') {
            return self::STATUS_FAILED;
        }

        return match (mb_strtolower($statusString)) {
            'success' => self::STATUS_SUCCESS,
            'pending' => self::STATUS_PENDING,
            default => self::STATUS_FAILED,
        };
    }

    /**
     * Safely get array from mixed response
     *
     * @return array<string, mixed>
     */
    protected function ensureArray(mixed $data): array
    {
        if (is_array($data)) {
            /** @var array<string, mixed> $data */
            return $data;
        }

        return [];
    }
}
