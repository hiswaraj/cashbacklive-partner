<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EarningType;
use Database\Factories\EarningFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property int $id
 * @property int $conversion_id
 * @property int|null $payout_id
 * @property EarningType $type
 * @property int $amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Conversion $conversion
 * @property-read Payout|null $payout
 */
final class Earning extends Model
{
    /** @use HasFactory<EarningFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => EarningType::class,
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The conversion that the earning belongs to.
     *
     * @return BelongsTo<Conversion, covariant $this>
     */
    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    /**
     * The payout that the earning belongs to.
     *
     * @return BelongsTo<Payout, covariant $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Earning $model): void {
            self::validateModel($model);
        });

        self::updating(function (Earning $model): void {
            self::validateModel($model);
        });
    }

    /**
     * Get the UPI address this earning is owed to.
     */
    protected function upi(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Eager load conversion
                $conversion = Conversion::where('id', $this->conversion_id)->with('click.refer')->firstOrFail();

                return $this->type === EarningType::USER
                    ? $conversion->click->upi
                    : $conversion->click->refer?->upi;
            },
        )->shouldCache();
    }

    /**
     * Get the UPI address this earning is owed to.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Eager load conversion
                $payout = Payout::where('id', $this->payout_id)->first();

                return $payout?->status->value ?? 'unpaid';
            },
        )->shouldCache();
    }

    /**
     * Validate the model attributes.
     *
     * @throws ValidationException
     */
    private static function validateModel(self $model): void
    {
        if ($model->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The amount must be greater than zero.',
            ]);
        }

        if (empty($model->type)) {
            throw ValidationException::withMessages([
                'type' => 'The type cannot be empty.',
            ]);
        }
    }
}
