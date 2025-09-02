<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum PayoutStatus: string implements HasColor, HasIcon
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';

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
            self::PENDING => 'warning',
            self::SUCCESS => 'success',
            self::FAILED => 'danger'
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::SUCCESS => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
        };
    }
}
