<?php

declare(strict_types=1);

namespace App\Livewire\CampaignTracker;

use App\Models\Campaign;
use App\Rules\UPI;
use App\Settings\CaptchaSettings;
use App\Settings\GeneralSettings;
use App\Traits\WithReCaptcha;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Search extends Component
{
    use WithReCaptcha;

    #[Locked]
    public ?Campaign $campaign = null;

    public string $upi = '';

    /**
     * @return array<string, array<int, string|callable>>
     */
    public function rules(): array
    {
        return [
            'upi' => [
                'required',
                new UPI,
            ],
        ];
    }

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function render(): View
    {
        return view('livewire.campaign-tracker.search')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return "{$appName} | Campaign Tracker | {$this->campaign->name}";
    }

    public function submit(): void
    {
        // Add captcha validation if enabled
        if (app(CaptchaSettings::class)->enable_captcha_in_tracker_page) {
            $this->verifyRecaptcha();
        }

        $this->validate();

        $upi = $this->upi;
        $this->reset('upi');

        $this->redirect(route('campaign-tracker.report', [
            'campaign' => $this->campaign->id,
            'upi' => $upi,
        ]), navigate: true);
    }
}
