<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExtraInputType;
use App\Models\Campaign;
use App\Models\Click;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Click>
 */
final class ClickFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Click>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Click>
     */
    protected $model = Click::class;

    /**
     * @var array<string>
     */
    private array $upiProviders = [
        'okbizaxis', 'okhdfcbank', 'okicici', 'oksbi',
        'paytm', 'ybl', 'apl', 'ibl', 'upi',
        'gpay', 'phonepay', 'axisbank',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $created_at = $this->faker->dateTimeBetween('-2 months');

        return [
            'campaign_id' => Campaign::factory(),
            'upi' => fake()->userName().'@'.$this->upiProviders[array_rand($this->upiProviders)],
            'ip_address' => fake()->ipv4(),

            'extra_input_1' => function (array $attrs): string {
                /** @var Campaign $campaign */
                $campaign = Campaign::find($attrs['campaign_id']);

                return $this->generateExtraInput($campaign->extra_input_1_type ?? ExtraInputType::TEXT);
            },

            'extra_input_2' => function (array $attrs): string {
                /** @var Campaign $campaign */
                $campaign = Campaign::find($attrs['campaign_id']);

                return $this->generateExtraInput($campaign->extra_input_2_type ?? ExtraInputType::TEXT);
            },

            'extra_input_3' => function (array $attrs): string {
                /** @var Campaign $campaign */
                $campaign = Campaign::find($attrs['campaign_id']);

                return $this->generateExtraInput($campaign->extra_input_3_type ?? ExtraInputType::TEXT);
            },

            'created_at' => $created_at,
            'updated_at' => $this->faker->boolean() ? $this->faker->dateTimeBetween($created_at) : null,
        ];
    }

    /**
     * Generate valid extra input based on type
     */
    private function generateExtraInput(ExtraInputType $type): string
    {
        return match ($type) {
            ExtraInputType::NUMBER => (string) fake()->numberBetween(1, 1000),
            ExtraInputType::MOBILE => '9'.fake()->numerify('#########'),
            ExtraInputType::EMAIL => fake()->safeEmail(),
            ExtraInputType::GAID => mb_strtoupper(fake()->uuid()),
            ExtraInputType::TEXT => fake()->word(),
        };
    }
}
