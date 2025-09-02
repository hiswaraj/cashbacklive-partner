<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Event;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversion>
 */
final class ConversionFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Conversion>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Conversion>
     */
    protected $model = Conversion::class;

    public function definition(): array
    {
        $campaign = Campaign::factory()->make();

        $isValid = $this->faker->boolean;
        $created_at = $this->faker->dateTimeBetween('-2 months');

        return [
            'click_id' => Click::factory(['campaign_id' => $campaign->id]),
            'event_id' => Event::factory(['campaign_id' => $campaign->id]),
            'is_valid' => $isValid,
            'reason' => $isValid ? null : $this->faker->sentence(),
            'ip_address' => fake()->ipv4(),
            'created_at' => $created_at,
            'updated_at' => $this->faker->boolean() ? $this->faker->dateTimeBetween($created_at) : null,
        ];
    }
}
