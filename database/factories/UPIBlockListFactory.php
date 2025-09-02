<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UPIBlockList;
use Database\Factories\Concerns\RefreshOnCreate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UPIBlockList>
 */
final class UPIBlockListFactory extends Factory
{
    /**
     * @use RefreshOnCreate<UPIBlockList>
     */
    use RefreshOnCreate;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UPIBlockList>
     */
    protected $model = UPIBlockList::class;

    public function definition(): array
    {
        return [
            'string' => fake()->unique()->userName(),
            'block_reason' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the block does not have a block_reason.
     */
    public function withoutBlockReason(): self
    {
        return $this->state(fn (array $attributes): array => [
            'block_reason' => null,
        ]);
    }
}
