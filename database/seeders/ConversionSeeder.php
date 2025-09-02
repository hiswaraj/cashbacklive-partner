<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Click;
use App\Models\Conversion;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class ConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all clicks
        Click::all()->each(function (Click $click): void {
            // Get events belonging to the click's campaign
            $campaignEvents = Event::where('campaign_id', $click->campaign_id)->get();

            // Randomly select 0 to 2 events without repetition
            $desiredCount = random_int(0, 2);
            $actualCountToSelect = min($desiredCount, $campaignEvents->count());
            $selectedEvents = $actualCountToSelect > 0 ? $campaignEvents->random($actualCountToSelect) : collect();

            // Ensure $selectedEvents is always a collection for the foreach loop,
            // as ->random(1) returns a single item, not a collection.
            if ($actualCountToSelect === 1 && $selectedEvents !== null && ! $selectedEvents instanceof Collection) {
                $selectedEvents = collect([$selectedEvents]);
            }

            foreach ($selectedEvents as $event) {
                Conversion::factory()->create([
                    'click_id' => $click->id,
                    'event_id' => $event->id,
                    'ip_address' => $click->ip_address,
                ]);
            }
        });
    }
}
