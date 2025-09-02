<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessPolicy;
use App\Enums\ExtraInputType;
use App\Enums\ReferralPolicy;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * @property string $id
 * @property string $name
 * @property string $subtitle
 * @property string|null $logo_path
 * @property string $description
 * @property string $terms
 * @property string $url
 * @property bool $is_active
 * @property AccessPolicy $access_policy
 * @property ReferralPolicy $referral_policy
 * @property bool $is_footer_telegram_enabled
 * @property bool $is_referer_telegram_allowed
 * @property bool $is_telegram_enabled_on_404
 * @property bool $is_auto_redirect_to_telegram_on_404
 * @property bool $is_direct_redirect
 * @property string $webhook_secret
 * @property int $max_upi_attempts
 * @property int $max_ip_attempts
 * @property bool $is_extra_input_1_active
 * @property bool $is_extra_input_1_required
 * @property ExtraInputType $extra_input_1_type
 * @property string|null $extra_input_1_label
 * @property bool $is_extra_input_2_active
 * @property bool $is_extra_input_2_required
 * @property ExtraInputType $extra_input_2_type
 * @property string|null $extra_input_2_label
 * @property bool $is_extra_input_3_active
 * @property bool $is_extra_input_3_required
 * @property ExtraInputType $extra_input_3_type
 * @property string|null $extra_input_3_label
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Refer[] $refers
 * @property-read Click[] $clicks
 * @property-read Event[] $events
 */
final class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'access_policy' => AccessPolicy::class,
        'referral_policy' => ReferralPolicy::class,
        'is_referer_telegram_allowed' => 'boolean',
        'is_footer_telegram_enabled' => 'boolean',
        'is_telegram_enabled_on_404' => 'boolean',
        'is_auto_redirect_to_telegram_on_404' => 'boolean',
        'is_direct_redirect' => 'boolean',
        'max_upi_attempts' => 'integer',
        'max_ip_attempts' => 'integer',
        'is_extra_input_1_active' => 'boolean',
        'is_extra_input_1_required' => 'boolean',
        'extra_input_1_type' => ExtraInputType::class,
        'is_extra_input_2_active' => 'boolean',
        'is_extra_input_2_required' => 'boolean',
        'extra_input_2_type' => ExtraInputType::class,
        'is_extra_input_3_active' => 'boolean',
        'is_extra_input_3_required' => 'boolean',
        'extra_input_3_type' => ExtraInputType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the refers for the campaign.
     *
     * @return HasMany<Refer, covariant $this>
     */
    public function refers(): HasMany
    {
        return $this->hasMany(Refer::class);
    }

    /**
     * Get the clicks for the campaign.
     *
     * @return HasMany<Click, covariant $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Get the events for the campaign.
     *
     * @return HasMany<Event, covariant $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Scope a query to only active campaigns.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only public campaigns.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('access_policy', AccessPolicy::PUBLIC);
    }

    /**
     * Scope a query to only campaigns with referrals enabled.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopeReferralsOpen(Builder $query): Builder
    {
        return $query->where('referral_policy', ReferralPolicy::OPEN);
    }

    public function totalUserAmount(): int
    {
        // Use eager-loaded sum if available, otherwise fallback to a new query.
        if (isset($this->events_sum_user_amount)) {
            return (int) $this->events_sum_user_amount;
        }

        return (int) $this->events()->sum('user_amount');
    }

    public function totalReferAmount(): int
    {
        // Use eager-loaded sum if available, otherwise fallback to a new query.
        if (isset($this->events_sum_refer_amount)) {
            return (int) $this->events_sum_refer_amount;
        }

        return (int) $this->events()->sum('refer_amount');
    }

    public function duplicate(): self
    {
        $newCampaign = $this->replicate(['id']);
        $newCampaign->id = self::generateUniqueId();
        $newCampaign->is_active = false; // Default to inactive for safety
        $newCampaign->access_policy = AccessPolicy::PRIVATE;
        $newCampaign->push();

        // Duplicate events
        foreach ($this->events as $event) {
            $newEvent = $event->replicate();
            $newEvent->campaign_id = $newCampaign->id;
            $newEvent->save();
        }

        return $newCampaign;
    }

    /**
     * The "boot" method of the model.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Campaign $model): void {
            $model->id = self::generateUniqueId();
            self::validateModel($model);
        });

        self::updating(function (Campaign $model): void {
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
        if (empty($model->name)) {
            throw ValidationException::withMessages([
                'name' => 'The campaign name cannot be empty.',
            ]);
        }

        if (empty($model->subtitle)) {
            throw ValidationException::withMessages([
                'subtitle' => 'The campaign subtitle cannot be empty.',
            ]);
        }

        if (empty($model->description)) {
            throw ValidationException::withMessages([
                'description' => 'The campaign description cannot be empty.',
            ]);
        }

        if (empty($model->terms)) {
            throw ValidationException::withMessages([
                'terms' => 'The campaign terms cannot be empty.',
            ]);
        }

        if (empty($model->url)) {
            throw ValidationException::withMessages([
                'url' => 'The campaign url cannot be empty.',
            ]);
        }

        if (! filter_var($model->url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'url' => 'Invalid campaign url.',
            ]);
        }

        if (empty($model->webhook_secret)) {
            throw ValidationException::withMessages([
                'webhook_secret' => 'The campaign webhook_secret cannot be empty.',
            ]);
        }

        if ($model->max_upi_attempts < 0) {
            throw ValidationException::withMessages([
                'max_upi_attempts' => 'The campaign max_upi_attempts must be a non-negative value.',
            ]);
        }

        if ($model->max_ip_attempts < 0) {
            throw ValidationException::withMessages([
                'max_ip_attempts' => 'The campaign max_ip_attempts must be a non-negative value.',
            ]);
        }

        // extra_input_1_type validation not needed | throws not a valid backing value for enum App\Enums\ExtraInputType

        if ($model->is_extra_input_1_active && empty($model->extra_input_1_label)) {
            throw ValidationException::withMessages([
                'extra_input_1_label' => 'The extra_input_1_label cannot be empty.',
            ]);
        }

        // extra_input_2_type validation not needed | throws not a valid backing value for enum App\Enums\ExtraInputType

        if ($model->is_extra_input_2_active && empty($model->extra_input_2_label)) {
            throw ValidationException::withMessages([
                'extra_input_2_label' => 'The extra_input_2_label cannot be empty.',
            ]);
        }

        // extra_input_3_type validation not needed | throws not a valid backing value for enum App\Enums\ExtraInputType

        if ($model->is_extra_input_3_active && empty($model->extra_input_3_label)) {
            throw ValidationException::withMessages([
                'extra_input_3_label' => 'The extra_input_3_label cannot be empty.',
            ]);
        }
    }
}
