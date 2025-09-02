<?php

declare(strict_types=1);

namespace App\Livewire\CampaignTracker;

use App\Enums\AccessPolicy;
use App\Models\Campaign;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Index extends Component
{
    /** @var Collection<int, Campaign> */
    #[Locked]
    public Collection $campaigns;

    public ?string $selectedCampaignId = null;

    /**
     * @return array<string, array<int, string|callable>>
     */
    public function rules(): array
    {
        return [
            'selectedCampaignId' => [
                'required',
                'exists:campaigns,id,is_active,1,access_policy,'.AccessPolicy::PUBLIC->value,
            ],
        ];
    }

    public function mount(): void
    {
        $this->campaigns = Campaign::query()
            ->active()->public()
            ->latest()
            ->get(['id', 'name', 'logo_path']);
    }

    public function render(): View
    {
        return view('livewire.campaign-tracker.index')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return "$appName | Campaign Tracker";
    }

    public function updatedSelectedCampaignId(Campaign $value): void
    {
        $this->validate();
        $this->redirect(route('campaign-tracker.search', ['campaign' => $value]));
    }
}
