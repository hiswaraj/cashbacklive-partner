<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum EarningType: string implements HasColor, HasIcon
{
    case REFER = 'refer';
    case USER = 'user';

    /**
     * @return array<string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::USER => 'info',
            self::REFER => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::USER => 'heroicon-o-user',
            self::REFER => 'heroicon-o-share',
        };
    }
}
