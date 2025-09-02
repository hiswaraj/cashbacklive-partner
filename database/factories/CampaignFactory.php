<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccessPolicy;
use App\Enums\ExtraInputType;
use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
final class CampaignFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Campaign>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Campaign>
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Basic info
            'name' => fake()->company(),
            'subtitle' => fake()->sentence(),
            'logo_path' => fake()->imageUrl(),
            'description' => fake()->paragraph(),
            'terms' => fake()->paragraph(),
            'url' => 'https://example.com/redirect?click_id={click_id}',

            // Settings & Configuration
            'is_active' => fake()->boolean(),
            'access_policy' => fake()->randomElement(AccessPolicy::cases()),
            'referral_policy' => fake()->randomElement(ReferralPolicy::cases()),
            'is_referer_telegram_allowed' => fake()->boolean(),
            'is_footer_telegram_enabled' => fake()->boolean(),
            'is_telegram_enabled_on_404' => fake()->boolean(),
            'is_auto_redirect_to_telegram_on_404' => fake()->boolean(),
            'is_direct_redirect' => fake()->boolean(),

            // Limits & Security
            'webhook_secret' => fake()->uuid(),
            'max_upi_attempts' => fake()->numberBetween(1, 10),
            'max_ip_attempts' => fake()->numberBetween(1, 10),

            // Extra Input Settings
            'is_extra_input_1_active' => fake()->boolean(),
            'is_extra_input_1_required' => fake()->boolean(),
            'extra_input_1_type' => fake()->randomElement(ExtraInputType::values()),
            'extra_input_1_label' => fake()->word(),

            'is_extra_input_2_active' => fake()->boolean(),
            'is_extra_input_2_required' => fake()->boolean(),
            'extra_input_2_type' => fake()->randomElement(ExtraInputType::values()),
            'extra_input_2_label' => fake()->word(),

            'is_extra_input_3_active' => fake()->boolean(),
            'is_extra_input_3_required' => fake()->boolean(),
            'extra_input_3_type' => fake()->randomElement(ExtraInputType::values()),
            'extra_input_3_label' => fake()->word(),
        ];
    }
}
