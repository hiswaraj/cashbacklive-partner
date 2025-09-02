<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Event;
use Illuminate\Database\Seeder;

final class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create events for each campaign
        Campaign::all()->each(function (Campaign $campaign): void {
            // Create 2-4 events per campaign
            Event::factory()
                ->count(fake()->numberBetween(2, 4))
                ->for($campaign)
                ->create();
        });
    }
}
