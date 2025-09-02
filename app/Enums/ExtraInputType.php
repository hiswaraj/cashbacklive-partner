<?php

declare(strict_types=1);

namespace App\Enums;

enum ExtraInputType: string
{
    case NUMBER = 'number';
    case EMAIL = 'email';
    case MOBILE = 'tel';
    case GAID = 'gaid';
    case TEXT = 'text';

    /**
     * @return array<string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
