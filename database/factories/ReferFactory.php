<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Refer;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refer>
 */
final class ReferFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Refer>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Refer>
     */
    protected $model = Refer::class;

    /**
     * @var array<string>
     */
    private array $upiProviders = [
        'okbizaxis', 'okhdfcbank', 'okicici', 'oksbi',
        'paytm', 'ybl', 'apl', 'ibl', 'upi',
    ];

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'upi' => fake()->userName().'@'.$this->upiProviders[array_rand($this->upiProviders)],
            'mobile' => '9'.fake()->numerify('#########'),
            'telegram_url' => 'https://t.me/'.fake()->userName(),
            'commission_split_settings' => null, // Default to no specific split
        ];
    }
}
