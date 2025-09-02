<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
final class EventFactory extends Factory
{
    /**
     * @use RefreshOnCreate<Event>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Event>
     */
    protected $model = Event::class;

    public function definition(): array
    {
        $userAmount = fake()->numberBetween(1, 100);
        $referAmount = fake()->numberBetween(0, 50);
        $isSplitAllowed = fake()->boolean();

        $minReferCommission = 0;
        $maxReferCommission = 0;

        if ($isSplitAllowed) {
            $totalCommission = $userAmount + $referAmount;
            if ($totalCommission > 0) {
                $minReferCommission = fake()->numberBetween(0, $referAmount);
                $maxReferCommission = fake()->numberBetween($minReferCommission, $totalCommission);
            }
        }

        return [
            'param' => fake()->bothify('????-###'), // Some tests in filament failed when using fake()->word() and got <= 4 characters
            'label' => ucfirst(fake()->word()),
            'user_amount' => $userAmount,
            'user_payment_comment' => fake()->sentence(),
            'is_instant_pay_user' => fake()->boolean(),
            'refer_amount' => $referAmount,
            'referrer_payment_comment' => fake()->sentence(),
            'is_instant_pay_refer' => fake()->boolean(),
            'is_commission_split_allowed' => $isSplitAllowed,
            'min_refer_commission' => $minReferCommission,
            'max_refer_commission' => $maxReferCommission,
            'time_gap_in_seconds' => fake()->numberBetween(60, 120),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
