<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccessPolicy;
use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use Illuminate\Database\Seeder;

final class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an active, public campaign with open referrals
        Campaign::factory()->create([
            'name' => 'Hola Referral Program',
            'subtitle' => 'Earn ₹100 for each referral',
            'description' => 'Refer your friends to Hola and earn rewards!',
            'terms' => 'Boom! 💥',
            'url' => 'https://hola.com/redirect?click_id={click_id}',
            'is_active' => true,
            'access_policy' => AccessPolicy::PUBLIC,
            'referral_policy' => ReferralPolicy::OPEN,
            'is_referer_telegram_allowed' => true,
            'is_footer_telegram_enabled' => true,
            'is_telegram_enabled_on_404' => true,
            'is_auto_redirect_to_telegram_on_404' => true,
            'is_direct_redirect' => false,
        ]);

        // Create an inactive campaign (hard-off)
        Campaign::factory()->create([
            'name' => 'Past Campaign',
            'is_active' => false,
            'access_policy' => AccessPolicy::PUBLIC,
            'referral_policy' => ReferralPolicy::OPEN,
            'is_referer_telegram_allowed' => true,
            'is_direct_redirect' => false,
        ]);

        // Create a private campaign (paused but can process webhooks)
        Campaign::factory()->create([
            'name' => 'Private Campaign',
            'is_active' => true,
            'access_policy' => AccessPolicy::PRIVATE,
            'is_direct_redirect' => false,
        ]);

        // Create a campaign with referrals disabled
        Campaign::factory()->create([
            'name' => 'No Referral Program',
            'is_active' => true,
            'access_policy' => AccessPolicy::PUBLIC,
            'referral_policy' => ReferralPolicy::DISABLED,
            'is_direct_redirect' => false,
        ]);
    }
}
