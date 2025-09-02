<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ConversionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversion $conversion
    ) {}
}
