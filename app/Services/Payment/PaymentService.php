<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Events\PaymentStatusUpdated;
use App\Jobs\ProcessPayout;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Earning;
use App\Models\Event;
use App\Models\Payout;
use App\Rules\UPI as UpiRule;
use App\Settings\PaymentGatewaySettings;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

final readonly class PaymentService
{
    public function __construct(
        private PaymentGatewayResolver $gatewayResolver,
        private PaymentGatewaySettings $settings
    ) {}

    /**
     * Creates earnings for a given conversion and, if applicable, initiates an instant payout.
     *
     * @return Collection<int, Earning> The collection of created earnings.
     */
    public function processEarningsForConversion(
        Conversion $conversion,
        ?bool $forceInstantPayUser = null,
        ?bool $forceInstantPayReferrer = null
    ): Collection {
        // Get click and event, not eager loaded
        $click = Click::where('id', $conversion->click_id)->with('refer')->firstOrFail();
        $event = Event::where('id', $conversion->event_id)->firstOrFail();

        /** @var Collection<int, Earning> $createdEarnings */
        $createdEarnings = collect([]);
        $instantPayEarnings = collect([]);

        $finalUserAmount = $conversion->calculated_amounts['user'];
        $finalReferAmount = $conversion->calculated_amounts['refer'];

        // Determine if payment should be instant, respecting the admin's override.
        $shouldInstantPayUser = $forceInstantPayUser ?? $event->is_instant_pay_user;
        $shouldInstantPayReferrer = $forceInstantPayReferrer ?? $event->is_instant_pay_refer;

        // Create User Earning
        if ($finalUserAmount > 0) {
            $userEarning = $this->createUnpaidEarning($conversion, EarningType::USER, $finalUserAmount);
            $createdEarnings->push($userEarning);
            if ($shouldInstantPayUser) {
                $instantPayEarnings->push($userEarning);
            }
        }

        // Create Referrer Earning
        if ($click->refer_id && $finalReferAmount > 0) {
            $referEarning = $this->createUnpaidEarning($conversion, EarningType::REFER, $finalReferAmount);
            $createdEarnings->push($referEarning);
            if ($shouldInstantPayReferrer) {
                $instantPayEarnings->push($referEarning);
            }
        }

        // If there are any earnings marked for instant payment, process them.
        if ($instantPayEarnings->isNotEmpty()) {
            // Group by UPI and initiate a separate payout for each recipient.
            $groupedByUpi = $instantPayEarnings->groupBy(fn (Earning $e) => $e->upi);

            foreach ($groupedByUpi as $upi => $earnings) {
                $this->initiatePayoutForEarnings($earnings);
            }
        }

        return $createdEarnings;
    }

    /**
     * The core atomic function to create a payout for a collection of earnings.
     * This is used by both instant pay and manual batching.
     */
    public function initiatePayoutForEarnings(Collection $earnings, ?string $comment = null, ?string $overrideUpi = null): Payout
    {
        if ($earnings->isEmpty()) {
            throw new LogicException('Cannot initiate a payout for an empty collection of earnings.');
        }

        // Group earnings by their intended UPI recipient
        $groupedByUpi = $earnings->groupBy(fn (Earning $earning) => $earning->upi);

        if ($overrideUpi === null && $groupedByUpi->count() > 1) {
            throw new LogicException('All earnings in a single payout must belong to the same UPI address unless an override UPI is provided.');
        }

        // Use the override UPI if provided, otherwise infer from the earnings group.
        $upi = $overrideUpi ?? $groupedByUpi->keys()->first();
        $totalAmount = $earnings->sum('amount');

        // If a comment isn't provided (e.g., for batch payouts), generate one.
        $payoutComment = $comment ?? $this->generatePayoutComment($earnings->first());

        return DB::transaction(function () use ($earnings, $upi, $totalAmount, $payoutComment) {
            // Lock the selected earnings to prevent race conditions.
            // This ensures no other process can assign them to another payout.
            $earningIds = $earnings->pluck('id')->all();
            Earning::whereIn('id', $earningIds)->whereNull('payout_id')->lockForUpdate();

            // Create the central Payout record.
            $payout = Payout::create([
                'upi' => $upi,
                'total_amount' => $totalAmount,
                'payment_gateway' => $this->settings->active_payment_gateway,
                'reference_id' => $this->generateReferenceId(),
                'status' => PayoutStatus::PENDING,
                'comment' => $payoutComment,
                'api_response' => 'Queued for processing.',
            ]);

            // Atomically assign all locked earnings to this new payout.
            Earning::whereIn('id', $earningIds)->update(['payout_id' => $payout->id]);

            // Dispatch the job to be processed by the queue worker.
            ProcessPayout::dispatch($payout);

            return $payout;
        });
    }

    /**
     * Atomically voids a failed payout and re-queues its earnings under a new payout with a corrected UPI.
     * This is the safe way to "retry" a payment.
     *
     * @param  Payout  $failedPayout  The original payout that failed.
     * @param  string  $correctedUpi  The new UPI address to send the payment to.
     * @param  string  $comment  The comment for the new payout.
     * @return Payout The newly created Payout instance.
     *
     * @throws LogicException|ValidationException
     */
    public function retryFailedPayout(Payout $failedPayout, string $correctedUpi, string $comment): Payout
    {
        // 1. Pre-flight validation
        if ($failedPayout->status !== PayoutStatus::FAILED) {
            throw new LogicException('Only failed payouts can be retried.');
        }

        $validator = Validator::make(['upi' => $correctedUpi], ['upi' => ['required', new UpiRule()]]);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // 2. Perform the entire operation in a single transaction
        return DB::transaction(function () use ($failedPayout, $correctedUpi, $comment) {
            $originalEarnings = $failedPayout->earnings()->lockForUpdate()->get();

            if ($originalEarnings->isEmpty()) {
                $failedPayout->save();
                throw new LogicException('Cannot retry a payout with no associated earnings.');
            }

            // 3. Dissociate earnings from the old, failed payout.
            $originalEarnings->toQuery()->update(['payout_id' => null]);

            $failedPayout->save();

            // 5. Create the new payout with the corrected info.
            // This re-uses your existing safe method.
            return $this->initiatePayoutForEarnings(
                earnings: $originalEarnings,
                comment: $comment,
                overrideUpi: $correctedUpi
            );
        });
    }

    /**
     * This method is called by the ProcessPayout job to execute the payment via the gateway.
     */
    public function executePayment(Payout $payout): void
    {
        try {
            // Resolve the active payment gateway for this execution
            $paymentGateway = $this->gatewayResolver->resolveByName($payout->payment_gateway);
            if (! $paymentGateway) {
                $paymentGateway = $this->gatewayResolver->resolveActive();
            }

            $paymentResult = $paymentGateway->processPayment(
                $payout->upi,
                $payout->total_amount,
                $payout->reference_id,
                $payout->comment
            );

            $payout->update([
                'status' => PayoutStatus::from($paymentResult['status']),
                'payment_id' => $paymentResult['payment_id'],
                'api_response' => $paymentResult['api_response'],
            ]);
        } catch (Exception $e) {
            $payout->update([
                'status' => PayoutStatus::FAILED,
                'api_response' => $e->getMessage(),
            ]);
            report($e);
            // We do not rethrow the exception, so the job is marked as completed.
            // A failed payout can be voided and retried manually from the admin panel.
        } finally {
            // Always dispatch the event to notify about the final status change (SUCCESS or FAILED).
            event(new PaymentStatusUpdated($payout->fresh()));
        }
    }

    /**
     * Creates a single, unpaid Earning record in the database.
     */
    private function createUnpaidEarning(Conversion $conversion, EarningType $type, int $amount): Earning
    {
        return Earning::create([
            'conversion_id' => $conversion->id,
            'payout_id' => null, // This signifies the earning is unpaid.
            'type' => $type,
            'amount' => $amount,
        ]);
    }

    private function generatePayoutComment(Earning $earning): string
    {
        $event = $earning->conversion->event;

        return ($earning->type === EarningType::USER)
            ? $event->user_payment_comment ?? ''
            : $event->referrer_payment_comment ?? '';
    }

    private function generateReferenceId(): string
    {
        $length = 24;

        $pool = array_merge(range('0', '9'), range('a', 'z'));
        $max = count($pool) - 1;
        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= $pool[random_int(0, $max)];
        }

        return $str;
    }
}
