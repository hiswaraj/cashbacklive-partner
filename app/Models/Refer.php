<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string $upi
 * @property string $mobile
 * @property string|null $telegram_url
 * @property array|null $commission_split_settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Campaign $campaign
 */
final class Refer extends Model
{
    /** @use HasFactory<ReferFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_split_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The campaign that the refer belongs to.
     *
     * @return BelongsTo<Campaign, covariant $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Refer $model): void {
            $model->id = self::generateUniqueId();
            self::validateModel($model);
        });

        self::updating(function (Refer $model): void {
            self::validateModel($model);
        });
    }

    /**
     * Generate a unique ID.
     */
    private static function generateUniqueId(): string
    {
        do {
            $id = mb_strtolower(Str::random(8));
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
        if (empty($model->upi)) {
            throw ValidationException::withMessages([
                'upi' => 'The refer upi cannot be empty.',
            ]);
        }

        if (in_array(preg_match('/^[6-9]\d{9}$/', $model->mobile), [0, false], true)) {
            throw ValidationException::withMessages([
                'mobile' => 'Must be a valid mobile number.',
            ]);
        }

        if ($model->telegram_url !== null && ! str_contains($model->telegram_url, 'https://t')) {
            throw ValidationException::withMessages([
                'telegram_url' => 'The telegram_url must be a valid telegram url.',
            ]);
        }

        if ($model->commission_split_settings !== null) {
            foreach ($model->commission_split_settings as $value) {
                if (! is_numeric($value)) {
                    throw ValidationException::withMessages([
                        'commission_split_settings' => 'All commission split values must be numeric.',
                    ]);
                }
            }
        }
    }
}
