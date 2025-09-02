<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Refer;
use Illuminate\Database\Seeder;

final class ClickSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all referrers and group them by campaign for efficient lookup
        $referrersByCampaign = Refer::all()->groupBy('campaign_id');
        $allCampaigns = Campaign::all();

        if ($allCampaigns->isEmpty()) {
            return;
        }

        // Create clicks with more varied dates and potential referrals
        Click::factory(100)
            ->state(function () use ($allCampaigns, $referrersByCampaign): array {
                $campaign = $allCampaigns->random();
                $state = ['campaign_id' => $campaign->id];

                // If this campaign has referrers and we randomly decide to make this a referred click (70% chance)
                if (isset($referrersByCampaign[$campaign->id]) && fake()->boolean(70)) {
                    $state['refer_id'] = $referrersByCampaign[$campaign->id]->random()->id;
                }

                return $state;
            })
            ->create();
    }
}
