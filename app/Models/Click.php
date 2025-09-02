<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraInputType;
use Database\Factories\ClickFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property string $id
 * @property string $campaign_id
 * @property null|string $refer_id
 * @property string $upi
 * @property string $ip_address
 * @property null|string $extra_input_1
 * @property null|string $extra_input_2
 * @property null|string $extra_input_3
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Campaign $campaign
 * @property-read Refer|null $refer
 * @property-read Conversion[] $conversions
 */
final class Click extends Model
{
    /** @use HasFactory<ClickFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The campaign that the click belongs to.
     *
     * @return BelongsTo<Campaign, covariant $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * The refer that the click belongs to.
     *
     * @return BelongsTo<Refer, covariant $this>
     */
    public function refer(): BelongsTo
    {
        return $this->belongsTo(Refer::class);
    }

    /**
     * Get the conversions for the click.
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

        self::creating(function (Click $model): void {
            $model->id = self::generateUniqueId();
            self::validateModel($model);
        });

        self::updating(function (Click $model): void {
            self::validateModel($model);
        });
    }

    /**
     * Generate a unique ID.
     */
    private static function generateUniqueId(): string
    {
        do {
            $id = mb_strtolower(Str::random(15));
        } while (self::where('id', $id)->exists());

        return $id;
    }

    /**
     * Validate the model attributes.
     *
     * @throws ValidationException
     */
    private static function validateModel(self $model): void
    {
        // Validate extra inputs based on campaign settings
        $campaign = Campaign::find($model->campaign_id);

        if (! $campaign) {
            throw ValidationException::withMessages(['campaign_id' => 'Campaign not found.']);
        }

        if (empty($model->upi)) {
            throw ValidationException::withMessages([
                'upi' => 'The upi cannot be empty.',
            ]);
        }

        if (! filter_var($model->ip_address, FILTER_VALIDATE_IP)) {
            throw ValidationException::withMessages(['ip_address' => 'The ip_address must be a valid IP address.']);
        }

        if ($campaign->is_extra_input_1_active && $campaign->is_extra_input_1_required && empty($model->extra_input_1)) {
            throw ValidationException::withMessages(['extra_input_1' => 'Extra Input 1 is required.']);
        }

        if ($campaign->is_extra_input_2_active && $campaign->is_extra_input_2_required && empty($model->extra_input_2)) {
            throw ValidationException::withMessages(['extra_input_2' => 'Extra Input 2 is required.']);
        }

        if ($campaign->is_extra_input_3_active && $campaign->is_extra_input_3_required && empty($model->extra_input_3)) {
            throw ValidationException::withMessages(['extra_input_3' => 'Extra Input 3 is required.']);
        }

        // Type validation
        if ($campaign->is_extra_input_1_active && ! empty($model->extra_input_1)) {
            self::validateInputType($model->extra_input_1, $campaign->extra_input_1_type, 'extra_input_1');
        }

        if ($campaign->is_extra_input_2_active && ! empty($model->extra_input_2)) {
            self::validateInputType($model->extra_input_2, $campaign->extra_input_2_type, 'extra_input_2');
        }

        if ($campaign->is_extra_input_3_active && ! empty($model->extra_input_3)) {
            self::validateInputType($model->extra_input_3, $campaign->extra_input_3_type, 'extra_input_3');
        }
    }

    private static function validateInputType(string $value, ExtraInputType $type, string $fieldName): void
    {
        switch ($type) {
            case ExtraInputType::NUMBER:
                if (! is_numeric($value)) {
                    throw ValidationException::withMessages([$fieldName => 'Must be a valid number.']);
                }
                break;

            case ExtraInputType::MOBILE:
                if (in_array(preg_match('/^[6-9]\d{9}$/', $value), [0, false], true)) {
                    throw ValidationException::withMessages([$fieldName => 'Must be a valid mobile number.']);
                }
                break;

            case ExtraInputType::EMAIL:
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages([$fieldName => 'Must be a valid email address.']);
                }
                break;

            case ExtraInputType::GAID:
                if (in_array(preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $value), [0, false], true)) {
                    throw ValidationException::withMessages([$fieldName => 'Must be a valid gaid.']);
                }
                break;

            case ExtraInputType::TEXT:
                break;
        }
    }
}
