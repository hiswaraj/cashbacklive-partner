<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property int $id
 * @property string $upi
 * @property int $total_amount
 * @property string $payment_gateway
 * @property string|null $payment_id
 * @property string $reference_id
 * @property PayoutStatus $status
 * @property string|null $comment
 * @property string|null $api_response
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Earning[] $earnings
 */
final class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'integer',
        'status' => PayoutStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the earnings for the conversion.
     *
     * @return HasMany<Earning, covariant $this>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Payout $model): void {
            self::validateModel($model);
        });

        self::updating(function (Payout $model): void {
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
        if (empty($model->upi)) {
            throw ValidationException::withMessages([
                'upi' => 'The upi cannot be empty.',
            ]);
        }

        if ($model->total_amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The amount must be greater than zero.',
            ]);
        }

        if (empty($model->payment_gateway)) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'The payment_gateway cannot be empty.',
            ]);
        }

        if (empty($model->reference_id)) {
            throw ValidationException::withMessages([
                'reference_id' => 'The reference_id cannot be empty.',
            ]);
        }

        if (empty($model->status)) {
            throw ValidationException::withMessages([
                'status' => 'The status cannot be empty.',
            ]);
        }
    }
}
