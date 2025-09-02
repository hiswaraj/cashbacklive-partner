<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferralPolicy: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case DISABLED = 'disabled';
}
