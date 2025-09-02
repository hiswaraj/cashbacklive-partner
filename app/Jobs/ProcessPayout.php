<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Services\Payment\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Payout $payout
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        // Idempotency Check: If the payout has been processed by other means
        // (e.g., a fast webhook) before the job runs, do nothing.
        if ($this->payout->fresh()->status !== PayoutStatus::PENDING) {
            return;
        }

        $paymentService->executePayment($this->payout);
    }
}
