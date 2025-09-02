<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Event;
use App\Models\UPIBlockList;
use App\Services\Payment\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class WebhookController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function handleConversion(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request);

            /** @var Click $click */
            $click = Click::with(['campaign', 'refer'])->findOrFail($data['click_id']);
            $event = $this->findEvent($click->campaign, $data['event']);

            $this->verifyWebhookSecret($click->campaign, $data['webhook_secret']);
            $this->checkConversionExists($click, $event);

            $result = DB::transaction(function () use ($click, $event) {
                /** @var array{click_id: string, event_id: string, is_valid: bool, reason?: string} $conversionData */
                $conversionData = $this->validateConversion($click, $event);
                $conversion = Conversion::create($conversionData);

                if (! $conversionData['is_valid']) {
                    return [
                        'status' => true,
                        'message' => 'Conversion recorded successfully, but '.($conversionData['reason'] ?? ''),
                    ];
                }

                // Create the earnings and trigger instant payouts if applicable.
                $this->paymentService->processEarningsForConversion($conversion);

                return [
                    'status' => true,
                    'message' => 'Conversion recorded and payments processed successfully.',
                ];
            });

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{click_id: string, event: string, webhook_secret: string}
     *
     * @throws Exception
     */
    private function validateRequest(Request $request): array
    {
        try {
            return $request->validate([
                'click_id' => 'required|string|exists:clicks,id',
                'event' => 'required|string:exists:events,param',
                'webhook_secret' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            throw new Exception('Invalid request parameters: '.implode(', ', array_map(
                fn ($messages): string => implode(', ', $messages),
                $e->errors()
            )), $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    private function findEvent(Campaign $campaign, string $eventName): Event
    {
        $event = Event::where('campaign_id', $campaign->id)
            ->where('param', $eventName)
            ->first();

        if (! $event) {
            throw new Exception('Event not found in this campaign');
        }

        return $event;
    }

    /**
     * @throws Exception
     */
    private function verifyWebhookSecret(Campaign $campaign, string $webhookSecret): void
    {
        if ($campaign->webhook_secret !== $webhookSecret) {
            throw new Exception('Invalid webhook secret');
        }
    }

    /**
     * @throws Exception
     */
    private function checkConversionExists(Click $click, Event $event): void
    {
        if (Conversion::where('click_id', $click->id)
            ->where('event_id', $event->id)
            ->exists()) {
            throw new Exception('Conversion already exists for this click and event');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateConversion(Click $click, Event $event): array
    {
        // @var array<string, mixed> $conversionData
        $conversionData = [
            'click_id' => $click->id,
            'event_id' => $event->id,
            'is_valid' => true,
            'ip_address' => request()->ip(),
        ];

        // If campaign is archived (hard-off), it cannot process new conversions.
        if (! $click->campaign->is_active) {
            return $this->invalidConversionData($conversionData, 'Campaign is archived');
        }

        $eventValidation = $this->validateEvents($click, $event);
        if (! $eventValidation['isValid']) {
            return $this->invalidConversionData($conversionData, $eventValidation['reason']);
        }

        if (UPIBlockList::isUpiBlocked($click->upi)) {
            return $this->invalidConversionData($conversionData, 'UPI is blocked');
        }

        if ($this->hasExceededUpiAttempts($click)) {
            return $this->invalidConversionData($conversionData, 'Maximum UPI attempts reached');
        }

        if ($this->hasExceededIpAttempts($click)) {
            return $this->invalidConversionData($conversionData, 'Maximum IP attempts reached');
        }

        return $conversionData;
    }

    /**
     * @param  array<string, mixed>  $conversionData
     * @return array<string, mixed>
     */
    private function invalidConversionData(array $conversionData, string $reason): array
    {
        $conversionData['is_valid'] = false;
        $conversionData['reason'] = $reason;

        return $conversionData;
    }

    private function hasExceededUpiAttempts(Click $click): bool
    {
        if ($click->campaign->is_direct_redirect) {
            return false;
        }

        $upiAttempts = Click::where('campaign_id', $click->campaign_id)
            ->where('upi', $click->upi)
            ->count();

        return $upiAttempts > $click->campaign->max_upi_attempts;
    }

    private function hasExceededIpAttempts(Click $click): bool
    {
        $ipAttempts = Click::where('campaign_id', $click->campaign_id)
            ->where('ip_address', $click->ip_address)
            ->count();

        return $ipAttempts > $click->campaign->max_ip_attempts;
    }

    /**
     * Checks if all previous events have been completed and if the time gap requirement is met
     *
     * @return array{isValid: bool, reason: string|null}
     */
    private function validateEvents(Click $click, Event $currentEvent): array
    {
        $campaign = $click->campaign;
        $previousEvents = Event::where('campaign_id', $campaign->id)
            ->where('sort_order', '<', $currentEvent->sort_order)
            ->orderBy('sort_order', 'asc')
            ->get();

        if ($previousEvents->isEmpty()) {
            return $this->checkTimeGap($click->created_at, $currentEvent->time_gap_in_seconds);
        }

        $completedPreviousEvents = Conversion::where('click_id', $click->id)
            ->where('is_valid', true)
            ->whereIn('event_id', $previousEvents->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($completedPreviousEvents->count() !== $previousEvents->count()) {
            return [
                'isValid' => false,
                'reason' => 'Previous events not completed',
            ];
        }

        // Check time gap from the most recent previous event
        return $this->checkTimeGap($completedPreviousEvents->first()->created_at, $currentEvent->time_gap_in_seconds);
    }

    /**
     * Checks if the required time gap has been met
     *
     * @return array{isValid: bool, reason: string|null}
     */
    private function checkTimeGap(Carbon $startTime, int $requiredGap): array
    {
        $actualGap = $startTime->diffInSeconds(now());
        if ($actualGap < $requiredGap) {
            return [
                'isValid' => false,
                'reason' => "Time gap not met. Required: $requiredGap seconds, Actual: $actualGap seconds",
            ];
        }

        return ['isValid' => true, 'reason' => null];
    }
}
