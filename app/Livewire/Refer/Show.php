<?php

declare(strict_types=1);

namespace App\Livewire\Refer;

use App\Enums\AccessPolicy;
use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Refer;
use App\Rules\UPI;
use App\Settings\CaptchaSettings;
use App\Settings\GeneralSettings;
use App\Traits\WithReCaptcha;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Show extends Component
{
    use WithReCaptcha;

    #[Locked]
    public Campaign $campaign;

    /** @var Collection<int, Event> */
    #[Locked]
    public Collection $splittableEvents;

    #[Locked]
    public int $nonSplittableReferAmountSum = 0;

    public string $upi = '';

    public string $mobile = '';

    public ?string $telegramUrl = null;

    /** @var array<int, int> */
    public array $commissionSplits = [];

    #[Locked]
    public ?string $referralLink = null;

    #[Locked]
    public ?string $trackerLink = null;

    public bool $showReferralLinkModal = false;

    /**
     * Define validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'upi' => ['required', new UPI],
            'mobile' => 'required|regex:/^[6-9]\d{9}$/',
            'telegramUrl' => ['nullable', 'url', 'max:255'],
            'commissionSplits' => 'array',
        ];

        foreach ($this->splittableEvents as $event) {
            $min = $event->min_refer_commission;
            $max = $event->max_refer_commission;
            $rules['commissionSplits.'.$event->id] = "required|numeric|min:{$min}|max:{$max}";
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->splittableEvents as $event) {
            $attributes['commissionSplits.'.$event->id] = "commission split for '{$event->label}' event";
        }

        return $attributes;
    }

    public function mount(Campaign $campaign): void
    {
        // Eager-load events and the sum of refer_amount to prevent N+1 queries.
        $this->campaign = $campaign->load('events')->loadSum('events', 'refer_amount');
        $this->validateCampaignConstraints();

        $allEvents = $this->campaign->events;

        $this->splittableEvents = $allEvents
            ->where('is_commission_split_allowed', true)
            ->sortBy('sort_order');

        // Calculate the sum for non-splittable events from the eager-loaded collection.
        $this->nonSplittableReferAmountSum = (int) $allEvents
            ->where('is_commission_split_allowed', false)
            ->sum('refer_amount');

        // Initialize commissionSplits with default referrer amounts, clamped within limits
        foreach ($this->splittableEvents as $event) {
            $min = $event->min_refer_commission;
            $max = $event->max_refer_commission;
            $this->commissionSplits[$event->id] = min($max, max($min, $event->refer_amount));
        }
    }

    /**
     * Live validation and sanitization for commission split values.
     */
    public function updatedCommissionSplits($value, string $key): void
    {
        $event = $this->splittableEvents->find($key);
        if (! $event) {
            return;
        }

        $min = $event->min_refer_commission;
        $max = $event->max_refer_commission;
        $sanitizedValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($sanitizedValue === false || $sanitizedValue < $min) {
            $sanitizedValue = $min;
        } elseif ($sanitizedValue > $max) {
            $sanitizedValue = $max;
        }

        $this->commissionSplits[$key] = $sanitizedValue;
        $this->validateOnly('commissionSplits.'.$key);
    }

    public function render(): View
    {
        return view('livewire.refers.show')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return "{$appName} | Refer&Earn | {$this->campaign->name}";
    }

    public function submit(): void
    {
        // Add captcha validation if enabled
        if (app(CaptchaSettings::class)->enable_captcha_in_refer_form) {
            $this->verifyRecaptcha();
        }

        $this->validateCampaignConstraints();
        $this->validate();

        $refer = $this->updateOrCreateRefer();

        $this->referralLink = route('short.campaign.show', ['campaign_or_refer_id' => $refer->id]);
        $this->trackerLink = route('short.refer-tracker.report', ['refer' => $refer->id]);
        $this->showReferralLinkModal = true;
    }

    /**
     * Close the success modal and reset the form for a new entry.
     */
    public function closeSuccessModal(): void
    {
        $this->showReferralLinkModal = false;
        $this->reset(['upi', 'mobile', 'telegramUrl', 'referralLink', 'trackerLink']);
        $this->resetValidation();

        // Re-initialize commissionSplits with default values, clamped within limits
        foreach ($this->splittableEvents as $event) {
            $min = $event->min_refer_commission;
            $max = $event->max_refer_commission;
            $this->commissionSplits[$event->id] = min($max, max($min, $event->refer_amount));
        }
    }

    /**
     * Ensure campaign is referable and active.
     */
    private function validateCampaignConstraints(): void
    {
        // To create a new referral link, campaign must be active, public, and have referrals open.
        if (
            ! $this->campaign->is_active ||
            $this->campaign->referral_policy !== ReferralPolicy::OPEN ||
            $this->campaign->access_policy === AccessPolicy::PRIVATE
        ) {
            abort(404, 'OVER !!', [
                'heading' => 'OVER !!',
                'subtext' => 'Sorry, You Are Late',
                'showTelegramOn404' => $this->campaign->is_telegram_enabled_on_404,
                'isRedirectToTelegram' => $this->campaign->is_auto_redirect_to_telegram_on_404,
            ]);
        }
    }

    private function updateOrCreateRefer(): Refer
    {
        return Refer::updateOrCreate([
            'campaign_id' => $this->campaign->id,
            'upi' => $this->upi,
        ], [
            'mobile' => $this->mobile,
            'telegram_url' => $this->telegramUrl,
            'commission_split_settings' => $this->commissionSplits,
        ]);
    }
}
