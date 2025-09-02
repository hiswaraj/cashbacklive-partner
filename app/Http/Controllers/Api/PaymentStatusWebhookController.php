<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PayoutStatus;
use App\Events\PaymentStatusUpdated;
use App\Models\Payout;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\PaymentGatewayResolver;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class PaymentStatusWebhookController
{
    public function __construct(
        private PaymentGatewayResolver $gatewayResolver,
    ) {}

    public function handlePaymentStatusUpdate(Request $request, string $gateway): JsonResponse
    {
        try {
            // Resolve the payment gateway
            $paymentGateway = $this->gatewayResolver->resolveByName($gateway);

            if (! $paymentGateway instanceof PaymentGateway) {
                return response()->json(['status' => false, 'message' => 'Invalid payment gateway']);
            }

            // Let the gateway parse the webhook data
            $paymentData = $paymentGateway->parseWebhookData($request->all());
            $gatewayIdentifier = $paymentData['payment_id'] ?? $paymentData['reference_id'] ?? null;

            if (! $gatewayIdentifier) {
                return response()->json(['status' => false, 'message' => 'Missing payment identifier in webhook payload.']);
            }

            // Find the Payout by payment_id or reference_id
            $payout = Payout::query()
                ->where(fn ($query) => $query->where('payment_id', $gatewayIdentifier)
                    ->orWhere('reference_id', $gatewayIdentifier))
                ->first();

            if (! $payout) {
                Log::warning('Payment webhook received for an unknown payout.', ['identifier' => $gatewayIdentifier]);

                return response()->json(['status' => false, 'message' => 'Payout not found']);
            }

            // Idempotency: If the payout is already settled, do nothing.
            if ($payout->status === PayoutStatus::SUCCESS || $payout->status === PayoutStatus::FAILED) {
                return response()->json(['status' => true, 'message' => 'Payout already settled.']);
            }

            // Update Payout status
            $newStatus = PayoutStatus::from($paymentData['status']);
            $payout->status = $newStatus;
            $payout->save();

            // Dispatch event for listeners (e.g., Telegram notifications)
            PaymentStatusUpdated::dispatch($payout);

            return response()->json(['status' => true, 'message' => 'Payout status updated successfully.']);

        } catch (Exception $e) {
            Log::error('Payment webhook error', [
                'gateway' => $gateway,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing webhook: '.$e->getMessage(),
            ]);
        }
    }
}
