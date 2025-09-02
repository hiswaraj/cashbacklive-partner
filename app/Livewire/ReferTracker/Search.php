<?php

declare(strict_types=1);

namespace App\Livewire\ReferTracker;

use App\Models\Campaign;
use App\Models\Refer;
use App\Rules\UPI;
use App\Settings\CaptchaSettings;
use App\Settings\GeneralSettings;
use App\Traits\WithReCaptcha;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Search extends Component
{
    use WithReCaptcha;

    #[Locked]
    public Campaign $campaign;

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
        return view('livewire.refer-tracker.search')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return "{$appName} | Referral Tracker | {$this->campaign->name}";
    }

    public function submit(): void
    {
        // Add captcha validation if enabled
        if (app(CaptchaSettings::class)->enable_captcha_in_tracker_page) {
            $this->verifyRecaptcha();
        }

        $this->validate();

        $refer = Refer::where('campaign_id', $this->campaign->id)
            ->where('upi', $this->upi)
            ->first();

        if (! $refer) {
            throw ValidationException::withMessages([
                'upi' => 'No referral record found with this UPI ID for the selected campaign.',
            ]);
        }

        $this->redirect(route('refer-tracker.report', [
            'refer' => $refer->id,
        ]));
    }
}
