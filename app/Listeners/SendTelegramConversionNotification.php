<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EarningType;
use App\Events\ConversionCreated;
use App\Models\Conversion;
use App\Services\Telegram\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class SendTelegramConversionNotification implements ShouldQueue
{
    public function __construct(
        private TelegramService $telegramService,
    ) {}

    public function handle(ConversionCreated $event): void
    {
        // Eager load all necessary relationships in a single go to prevent N+1 issues.
        $conversion = Conversion::with([
            'click.campaign',
            'click.refer',
            'event',
            'earnings.payout', // Load earnings and their related payouts
        ])->find($event->conversion->id);

        // If for some reason the conversion was deleted before the job ran.
        if (! $conversion) {
            return;
        }

        $message = $this->formatAdminMessage($conversion);
        $this->telegramService->sendMessageToAdmin($message);
    }

    private function formatAdminMessage(Conversion $conversion): string
    {
        $campaign = $conversion->click->campaign;
        $click = $conversion->click;
        $event = $conversion->event;

        $userPayment = $this->getPaymentDetails($conversion, EarningType::USER);
        $lines = [
            "🎁 <b>{$campaign->name} - {$event->label} Tracked</b> 💝",
            '-----------------------------------------',
            "<b>USER AMOUNT:</b> ₹{$userPayment['amount']}",
            "<b>USER UPI ID:</b> {$click->upi}",
            "<b>USER PAYMENT:</b> {$userPayment['status']}",
        ];

        if ($campaign->is_extra_input_1_active && $click->extra_input_1) {
            $lines[] = '<b>'.mb_strtoupper((string) $campaign->extra_input_1_label).":</b> {$click->extra_input_1}";
        }
        if ($campaign->is_extra_input_2_active && $click->extra_input_2) {
            $lines[] = '<b>'.mb_strtoupper((string) $campaign->extra_input_2_label).":</b> {$click->extra_input_2}";
        }
        if ($campaign->is_extra_input_3_active && $click->extra_input_3) {
            $lines[] = '<b>'.mb_strtoupper((string) $campaign->extra_input_3_label).":</b> {$click->extra_input_3}";
        }

        $lines[] = ''; // Blank line separator

        if ($click->refer) {
            $referPayment = $this->getPaymentDetails($conversion, EarningType::REFER);
            $lines = array_merge($lines, [
                "<b>REFER ID:</b> {$click->refer_id}",
                "<b>REFER AMOUNT:</b> ₹{$referPayment['amount']}",
                "<b>REFER UPI ID:</b> {$click->refer->upi}",
                "<b>REFER PAYMENT:</b> {$referPayment['status']}",
                "<b>REFER MOBILE:</b> {$click->refer->mobile}",
            ]);

            if ($click->refer->telegram_url) {
                $lines[] = "<b>REFER CHANNEL:</b> {$click->refer->telegram_url}";
            }
            $lines[] = ''; // Blank line separator
        }

        $lines = array_merge($lines, [
            "<b>EVENT TIME:</b> {$conversion->created_at->format('d-m-y h:i a')}",
            "<b>CONV. IP:</b> {$conversion->ip_address}",
        ]);

        if (! $conversion->is_valid) {
            $lines[] = '';
            $lines[] = "🚫 <b>INVALID CONVERSION</b>: {$conversion->reason}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get payment details from the new earnings/payouts structure.
     *
     * @return array{amount: int, status: string}
     */
    private function getPaymentDetails(Conversion $conversion, EarningType $type): array
    {
        $calculatedAmounts = $conversion->calculated_amounts;
        $amount = ($type === EarningType::USER) ? $calculatedAmounts['user'] : $calculatedAmounts['refer'];

        // Find the earning on the already loaded collection
        $earning = $conversion->earnings->firstWhere('type', $type);

        if (! $earning) {
            // No earning was created, likely because the amount was 0.
            return ['amount' => $amount, 'status' => 'N/A'];
        }

        if (! $earning->payout) {
            // An earning exists but hasn't been assigned to a payout yet.
            return ['amount' => $amount, 'status' => 'Unpaid (Awaiting Batch)'];
        }

        // An earning and payout exist, so report the payout's status.
        return ['amount' => $amount, 'status' => ucfirst($earning->payout->status->value)];
    }
}
