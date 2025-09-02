<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Campaign;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Home extends Component
{
    /**
     * Render the component.
     *
     * This method fetches all campaigns and renders the view that lists them,
     * using the main root layout.
     */
    public function render(): View
    {
        // Fetch all campaigns, ordered by the newest first.
        // The view already handles the logic for 'active' vs. 'inactive' campaigns.
        $campaigns = Campaign::latest()
            ->active()
            ->public()
            ->withSum('events', 'user_amount')
            ->withSum('events', 'refer_amount')
            ->get();

        return view('livewire.offers-list.campaigns', [
            'campaigns' => $campaigns,
        ])->layout('livewire.offers-list.root-layout');
    }
}
