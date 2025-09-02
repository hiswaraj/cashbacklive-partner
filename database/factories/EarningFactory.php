<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EarningType;
use App\Models\Conversion;
use App\Models\Earning;
use App\Models\Payout;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Earning>
 */
final class EarningFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Earning>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Earning>
     */
    protected $model = Earning::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversion_id' => Conversion::factory(),
            'payout_id' => null, // Default to unpaid
            'type' => fake()->randomElement(EarningType::cases()),
            'amount' => fake()->numberBetween(10, 500),
            'created_at' => fake()->dateTimeBetween('-2 months'),
            'updated_at' => fn (array $attributes) => fake()->dateTimeBetween($attributes['created_at']),
        ];
    }

    /**
     * Indicate that the earning has been paid via a specific payout.
     */
    public function withPayout(Payout $payout): self
    {
        return $this->state(fn (array $attributes): array => [
            'payout_id' => $payout->id,
        ]);
    }
}
