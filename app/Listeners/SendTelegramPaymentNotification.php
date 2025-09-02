<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentStatusUpdated;
use App\Models\Payout;
use App\Services\Telegram\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class SendTelegramPaymentNotification implements ShouldQueue
{
    public function __construct(
        private TelegramService $telegramService,
    ) {}

    public function handle(PaymentStatusUpdated $event): void
    {
        // Eager load relationships for efficiency
        $payout = $event->payout->load('earnings.conversion.click.campaign', 'earnings.conversion.event');

        $message = $this->formatAdminMessage($payout);
        $this->telegramService->sendMessageToAdmin($message);
    }

    private function formatAdminMessage(Payout $payout): string
    {
        $earningCount = $payout->earnings->count();

        $campaigns = $payout->earnings->map(fn ($e) => $e->conversion->click->campaign->name)->unique()->implode(', ');
        $eventLabels = $payout->earnings->map(fn ($e) => $e->conversion->event->label)->unique()->implode(', ');

        return "💰 <b>Payment Status Updated</b> 🔔\n"
            ."-----------------------------------------\n"
            ."<b>PAYOUT ID:</b> {$payout->id}\n"
            ."<b>CAMPAIGN(S):</b> {$campaigns}\n"
            ."<b>EVENT(S):</b> {$eventLabels}\n"
            ."<b>STATUS:</b> {$payout->status->value}\n"
            ."<b>AMOUNT:</b> ₹{$payout->total_amount}\n"
            ."<b>UPI ID:</b> {$payout->upi}\n"
            ."<b>REFERENCE ID:</b> {$payout->reference_id}\n"
            .($payout->payment_id ? "<b>PAYMENT ID:</b> {$payout->payment_id}\n" : '')
            ."<b>GATEWAY:</b> {$payout->payment_gateway}\n"
            .($payout->comment ? "<b>COMMENT:</b> {$payout->comment}\n" : '')
            ."\n"
            ."This payout includes <b>{$earningCount}</b> earning(s).\n";
    }
}
