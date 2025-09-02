<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
final class PayoutFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Payout>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payout>
     */
    protected $model = Payout::class;

    /**
     * @var array<string>
     */
    private array $upiProviders = [
        'okbizaxis', 'okhdfcbank', 'okicici', 'oksbi',
        'paytm', 'ybl', 'apl', 'ibl', 'upi',
    ];

    /**
     * @var array<string>
     */
    private array $paymentGateways = [
        'affxpay', 'bulkpe', 'kritarth', 'openmoney',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'upi' => fake()->userName().'@'.$this->upiProviders[array_rand($this->upiProviders)],
            'total_amount' => fake()->numberBetween(10, 500),
            'payment_gateway' => fake()->randomElement($this->paymentGateways),
            'payment_id' => fake()->uuid(),
            'reference_id' => fake()->uuid(),
            'status' => fake()->randomElement(PayoutStatus::cases()),
            'comment' => fake()->sentence(),
            'api_response' => json_encode(['message' => 'Factory generated response.']),
            'created_at' => fake()->dateTimeBetween('-2 months'),
            'updated_at' => fn (array $attributes) => fake()->dateTimeBetween($attributes['created_at']),
        ];
    }
}
