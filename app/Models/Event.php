<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property int $id
 * @property string $campaign_id
 * @property string $param
 * @property string $label
 * @property int $user_amount
 * @property string|null $user_payment_comment
 * @property bool $is_instant_pay_user
 * @property int $refer_amount
 * @property string|null $referrer_payment_comment
 * @property bool $is_instant_pay_refer
 * @property bool $is_commission_split_allowed
 * @property int $min_refer_commission
 * @property int $max_refer_commission
 * @property int $time_gap_in_seconds
 * @property int $sort_order
 * @property-read Campaign $campaign
 * @property-read Conversion[] $conversions
 */
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_amount' => 'integer',
        'is_instant_pay_user' => 'boolean',
        'refer_amount' => 'integer',
        'is_instant_pay_refer' => 'boolean',
        'is_commission_split_allowed' => 'boolean',
        'min_refer_commission' => 'integer',
        'max_refer_commission' => 'integer',
        'time_gap_in_seconds' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * The campaign that the event belongs to.
     *
     * @return BelongsTo<Campaign, covariant $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the conversions for the campaign.
     *
     * @return HasMany<Conversion, covariant $this>
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Event $model): void {
            self::validateModel($model);
        });

        self::updating(function (Event $model): void {
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
        if (empty($model->param)) {
            throw ValidationException::withMessages([
                'param' => 'The param cannot be empty.',
            ]);
        }

        if (empty($model->label)) {
            throw ValidationException::withMessages([
                'label' => 'The label cannot be empty.',
            ]);
        }

        if ($model->user_amount < 0) {
            throw ValidationException::withMessages([
                'user_amount' => 'The user_amount must be a non-negative value.',
            ]);
        }

        if ($model->refer_amount < 0) {
            throw ValidationException::withMessages([
                'refer_amount' => 'The refer_amount must be a non-negative value.',
            ]);
        }

        if ($model->user_payment_comment !== null && preg_match('/[\x00-\x1F\x7F]/', $model->user_payment_comment)) {
            throw ValidationException::withMessages([
                'user_payment_comment' => 'The user_payment_comment cannot contain special characters.',
            ]);
        }

        if ($model->referrer_payment_comment !== null && preg_match('/[\x00-\x1F\x7F]/', $model->referrer_payment_comment)) {
            throw ValidationException::withMessages([
                'referrer_payment_comment' => 'The referrer_payment_comment cannot contain special characters.',
            ]);
        }

        if ($model->min_refer_commission < 0) {
            throw ValidationException::withMessages([
                'min_refer_commission' => 'The min_refer_commission must be a non-negative value.',
            ]);
        }

        if ($model->max_refer_commission < 0) {
            throw ValidationException::withMessages([
                'max_refer_commission' => 'The max_refer_commission must be a non-negative value.',
            ]);
        }

        if ($model->is_commission_split_allowed && $model->max_refer_commission > $model->user_amount + $model->refer_amount) {
            throw ValidationException::withMessages(['max_refer_commission' => 'Max referrer commission cannot be greater than total commission.']);
        }

        if ($model->is_commission_split_allowed && $model->min_refer_commission > $model->max_refer_commission) {
            throw ValidationException::withMessages(['min_refer_commission' => 'Min referrer commission cannot be greater than max.']);
        }
    }
}
