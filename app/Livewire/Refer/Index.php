<?php

declare(strict_types=1);

namespace App\Livewire\Refer;

use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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
                'exists:campaigns,id,is_active,1,referral_policy,'.ReferralPolicy::OPEN->value,
            ],
        ];
    }

    public function mount(): void
    {
        $this->campaigns = Campaign::query()
            ->active()->public()->referralsOpen()
            ->latest()
            ->get(['id', 'name', 'logo_path']);
    }

    public function render(): View
    {
        return view('livewire.refers.index')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return $appName.' | Refer&Earn';
    }

    public function updatedSelectedCampaignId(Campaign $value): void
    {
        $this->validate();
        $this->redirect(route('refer.show', ['campaign' => $value]));
    }
}
