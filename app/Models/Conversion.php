<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\ConversionCreated;
use Database\Factories\ConversionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Override;

/**
 * @property int $id
 * @property string $click_id
 * @property int $event_id
 * @property bool $is_valid
 * @property string|null $reason
 * @property string $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Click $click
 * @property-read Event $event
 * @property-read Earning[] $earnings
 */
final class Conversion extends Model
{
    /** @use HasFactory<ConversionFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The click that the conversion belongs to.
     *
     * @return BelongsTo<Click, covariant $this>
     */
    public function click(): BelongsTo
    {
        return $this->belongsTo(Click::class);
    }

    /**
     * The event that the conversion belongs to.
     *
     * @return BelongsTo<Event, covariant $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

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

        self::creating(function (Conversion $model): void {
            self::validateModel($model);
        });

        self::updating(function (Conversion $model): void {
            self::validateModel($model);
        });

        self::created(function (Conversion $model): void {
            ConversionCreated::dispatch($model);
        });
    }

    /**
     * Get the calculated commission amounts for this conversion.
     */
    protected function calculatedAmounts(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $click = Click::where('id', $this->click_id)->with('refer')->firstOrFail();
                $event = Event::where('id', $this->event_id)->firstOrFail();

                $originalUserAmount = $event->user_amount;
                $originalReferAmount = $event->refer_amount;
                $totalCommission = $originalUserAmount + $originalReferAmount;

                $finalUserAmount = $originalUserAmount;
                $finalReferAmount = $originalReferAmount;

                // Check if a referrer exists, commission split is allowed for the event,
                // and a specific split setting is defined for this event by the referrer.
                if (
                    $click->refer_id &&
                    $event->is_commission_split_allowed &&
                    isset($click->refer->commission_split_settings[$event->id])
                ) {
                    $referrerShare = $click->refer->commission_split_settings[$event->id];

                    // Security Validation: Ensure the referrer's share is a valid number
                    // and does not exceed the total commission available for this event.
                    if (is_numeric($referrerShare)) {
                        $min = $event->min_refer_commission;
                        $max = $event->max_refer_commission;

                        $validatedReferrerShare = max($min, (int) $referrerShare);
                        $validatedReferrerShare = min($max, $validatedReferrerShare);

                        $finalReferAmount = $validatedReferrerShare;
                        $finalUserAmount = $totalCommission - $finalReferAmount;
                    }
                }

                return [
                    'user' => $finalUserAmount,
                    'refer' => $finalReferAmount,
                    'total' => $totalCommission,
                    'original_user' => $originalUserAmount,
                    'original_refer' => $originalReferAmount,
                ];
            }
        )->shouldCache(); // Cache the result per request
    }

    /**
     * Validate the model attributes.
     *
     * @throws InvalidArgumentException
     */
    private static function validateModel(self $model): void
    {
        $click = Click::find($model->click_id);
        $event = Event::find($model->event_id);

        if (! $click || ! $event) {
            throw new InvalidArgumentException('Click or Event not found');
        }

        if (! $model->is_valid && empty($model->reason)) {
            throw new InvalidArgumentException('Invalid conversion must have a reason');
        }

        if (! filter_var($model->ip_address, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('The ip_address must be a valid IP address.');
        }

        if ($click->campaign_id !== $event->campaign_id) {
            throw new InvalidArgumentException('Click and Event must belong to the same campaign');
        }
    }
}
