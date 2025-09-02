<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UPIBlockListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property int $id
 * @property string $string
 * @property string|null $block_reason
 */
final class UPIBlockList extends Model
{
    /** @use HasFactory<UPIBlockListFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'upi_blocklist';

    public static function isUpiBlocked(string $upi): bool
    {
        return self::get()->contains(fn (UPIBlockList $blockedUpi): bool => str_contains(mb_strtolower($upi), mb_strtolower($blockedUpi->string)));
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (UPIBlockList $model): void {
            self::validateModel($model);
        });

        self::updating(function (UPIBlockList $model): void {
            self::validateModel($model);
        });
    }

    /**
     * Validate the model attributes.
     *
     * @throws ValidationException
     */
    private static function validateModel(self $model): void
    {
        if (empty($model->string)) {
            throw ValidationException::withMessages([
                'string' => 'The string cannot be empty.',
            ]);
        }
    }
}
