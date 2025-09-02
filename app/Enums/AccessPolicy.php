<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessPolicy: string
{
    case PUBLIC = 'public';
    case UNLISTED = 'unlisted';
    case REFERRAL_ONLY = 'referral_only';
    case PRIVATE = 'private';
}
