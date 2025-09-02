<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Payout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class PaymentStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payout $payout
    ) {}
}
