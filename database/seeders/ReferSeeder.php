<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use App\Models\Refer;
use Illuminate\Database\Seeder;

final class ReferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all campaigns that allow referrals
        $referableCampaigns = Campaign::whereIn('referral_policy', [ReferralPolicy::OPEN, ReferralPolicy::CLOSED])->get();

        if ($referableCampaigns->isEmpty()) {
            return;
        }

        // Create 5-15 referrers for each referable campaign
        $referableCampaigns->each(function (Campaign $campaign): void {
            Refer::factory()
                ->count(fake()->numberBetween(5, 15))
                ->for($campaign)
                ->create();
        });
    }
}
