<?php

declare(strict_types=1);

namespace App\Livewire\Campaign;

use App\Enums\AccessPolicy;
use App\Enums\ExtraInputType;
use App\Enums\ReferralPolicy;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Refer;
use App\Rules\UPI;
use App\Settings\CaptchaSettings;
use App\Settings\GeneralSettings;
use App\Traits\WithReCaptcha;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Show extends Component
{
    use WithReCaptcha;

    #[Locked]
    public ?Campaign $campaign = null;

    #[Locked]
    public ?Refer $refer = null;

    public string $upi;

    public ?string $extra_input_1 = null;

    public ?string $extra_input_2 = null;

    public ?string $extra_input_3 = null;

    #[Locked]
    public string $displayState = 'form';

    public function mount(string $campaign_or_refer_id): void
    {
        $this->campaign = Campaign::with('events')->find($campaign_or_refer_id);

        // If campaign is not found, try to find with refer ID
        if (! $this->campaign instanceof Campaign) {
            $this->refer = Refer::with('campaign.events')->findOr($campaign_or_refer_id, fn () => abort(404, 'OVER !!', [
                'heading' => 'Campaign not found.',
            ]));
            $this->campaign = $this->refer->campaign;
        }

        $this->validateCampaignConstraints();

        // Handle direct refer redirect
        if ($this->campaign->is_direct_redirect) {
            $this->upi = 'N/A';

            $click = $this->createClick();
            $redirectUrl = str_replace('{click_id}', $click->id, $this->campaign->url);
            $this->redirect($redirectUrl);
        }
    }

    #[Computed]
    public function totalUserAmount(): int
    {
        $total = 0;
        // The events relationship is eager-loaded in mount()
        $events = $this->campaign->events;

        foreach ($events as $event) {
            $totalCommission = $event->user_amount + $event->refer_amount;
            $userShare = $event->user_amount;

            if (
                $this->refer &&
                $event->is_commission_split_allowed &&
                isset($this->refer->commission_split_settings[$event->id])
            ) {
                $refererShare = $this->refer->commission_split_settings[$event->id];

                if (is_numeric($refererShare)) {
                    $min = $event->min_refer_commission;
                    $max = $event->max_refer_commission;

                    $validatedRefererShare = max($min, (int) $refererShare);
                    $validatedRefererShare = min($max, $validatedRefererShare);

                    $userShare = $totalCommission - $validatedRefererShare;
                }
            }
            $total += $userShare;
        }

        return $total;
    }

    #[Computed]
    public function subtitle(): string
    {
        return str_replace('{user_amount}', (string) $this->totalUserAmount(), $this->campaign->subtitle);
    }

    public function rules(): array
    {
        $rules = [
            'upi' => [
                'required',
                new UPI,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->campaign instanceof Campaign) {
                        $currentAttempts = Click::where('campaign_id', $this->campaign->id)
                            ->where('upi', $value)
                            ->count();

                        if ($currentAttempts >= $this->campaign->max_upi_attempts) {
                            $fail('UPI limit reached.');
                        }
                    }
                },
            ],
        ];

        // Add rules for active custom inputs
        if ($this->campaign?->is_extra_input_1_active) {
            $rules['extra_input_1'] = $this->getCustomInputRules(
                $this->campaign->is_extra_input_1_required,
                $this->campaign->extra_input_1_type,
            );
        }

        if ($this->campaign?->is_extra_input_2_active) {
            $rules['extra_input_2'] = $this->getCustomInputRules(
                $this->campaign->is_extra_input_2_required,
                $this->campaign->extra_input_2_type,
            );
        }

        if ($this->campaign?->is_extra_input_3_active) {
            $rules['extra_input_3'] = $this->getCustomInputRules(
                $this->campaign->is_extra_input_3_required,
                $this->campaign->extra_input_3_type,
            );
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'upi.required' => 'Please enter your UPI ID.',
            'extra_input_1.required' => 'Please enter '.$this->campaign?->extra_input_1_label,
            'extra_input_2.required' => 'Please enter '.$this->campaign?->extra_input_2_label,
            'extra_input_3.required' => 'Please enter '.$this->campaign?->extra_input_3_label,
            'extra_input_1.email' => 'Please enter a valid email address',
            'extra_input_2.email' => 'Please enter a valid email address',
            'extra_input_3.email' => 'Please enter a valid email address',
            'extra_input_1.regex' => $this->getCustomInputErrorMessage($this->campaign?->extra_input_1_type),
            'extra_input_2.regex' => $this->getCustomInputErrorMessage($this->campaign?->extra_input_2_type),
            'extra_input_3.regex' => $this->getCustomInputErrorMessage($this->campaign?->extra_input_3_type),
            'extra_input_1.numeric' => 'Please enter a valid number',
            'extra_input_2.numeric' => 'Please enter a valid number',
            'extra_input_3.numeric' => 'Please enter a valid number',
        ];
    }

    public function render(): View
    {
        return view('livewire.campaigns.show')->title($this->pageTitle());
    }

    #[Computed]
    public function pageTitle(): string
    {
        $appName = app(GeneralSettings::class)->site_name;

        return "{$appName} | {$this->campaign?->name}";
    }

    public function submit(): void
    {
        // Add captcha validation if enabled
        if (app(CaptchaSettings::class)->enable_captcha_in_campaign_form) {
            $this->verifyRecaptcha();
        }

        $this->validateCampaignConstraints();
        if ($this->displayState !== 'form') {
            return;
        }

        $this->validate();

        // Create click record
        $click = $this->createClick();

        $redirectUrl = str_replace('{click_id}', $click->id, $this->campaign->url);

        // Reset form
        $this->reset(['upi', 'extra_input_1', 'extra_input_2', 'extra_input_3']);

        // Show success modal
        $this->displayState = 'success';

        // Dispatch redirect event with URL
        $this->dispatch('redirect', $redirectUrl);
    }

    private function getCustomInputErrorMessage(ExtraInputType $type): string
    {
        return match ($type) {
            ExtraInputType::MOBILE => 'Please enter a valid mobile number',
            ExtraInputType::GAID => 'Please enter a valid Google Advertising ID',
            default => 'Please enter a valid value',
        };
    }

    private function validateCampaignConstraints(): void
    {
        $isAllowed = false;

        // Master switch: if not active (archived), nothing is accessible.
        if (! $this->campaign->is_active) {
            $this->abortCampaign();
        }

        // Now, check access based on policy
        switch ($this->campaign->access_policy) {
            case AccessPolicy::PUBLIC:
            case AccessPolicy::UNLISTED:
                // Public and Unlisted campaigns are always accessible via direct link or referral.
                $isAllowed = true;
                break;

            case AccessPolicy::REFERRAL_ONLY:
                // Only accessible if a valid referral link is being used.
                if ($this->refer instanceof Refer) {
                    $isAllowed = true;
                }
                break;

            case AccessPolicy::PRIVATE:
                // Private campaigns are never accessible from the front-end.
                $isAllowed = false;
                break;
        }

        // If access is allowed, we must also check referral constraints.
        if ($isAllowed && $this->refer instanceof Refer) {
            // If a referral link is used, the campaign's referral system cannot be disabled.
            if ($this->campaign->referral_policy === ReferralPolicy::DISABLED) {
                $isAllowed = false;
            }
        }

        if (! $isAllowed) {
            $this->abortCampaign();
        }

        // Check IP attempts
        $currentAttempts = Click::where('campaign_id', $this->campaign->id)
            ->where('ip_address', request()->ip())
            ->count();

        if ($currentAttempts >= $this->campaign->max_ip_attempts) {
            $this->displayState = 'ip_limit_reached';
        }
    }

    private function abortCampaign(): void
    {
        abort(404, 'OVER !!', [
            'heading' => 'OVER !!',
            'subtext' => 'Sorry, You Are Late',
            'showTelegramOn404' => $this->campaign->is_telegram_enabled_on_404,
            'isRedirectToTelegram' => $this->campaign->is_auto_redirect_to_telegram_on_404,
        ]);
    }

    private function createClick(): Click
    {
        return Click::create([
            'campaign_id' => $this->campaign->id,
            'refer_id' => $this->refer?->id,
            'upi' => $this->upi,
            'ip_address' => request()->ip(),
            'extra_input_1' => $this->extra_input_1,
            'extra_input_2' => $this->extra_input_2,
            'extra_input_3' => $this->extra_input_3,
        ]);
    }

    private function getCustomInputRules(bool $isRequired, ExtraInputType $type): array
    {
        $rules = [];

        $rules[] = $isRequired ? 'required' : 'nullable';

        switch ($type) {
            case ExtraInputType::NUMBER:
                $rules[] = 'numeric';
                break;
            case ExtraInputType::MOBILE:
                $rules[] = 'regex:/^[6-9]\d{9}$/';
                break;
            case ExtraInputType::EMAIL:
                $rules[] = 'email';
                break;
            case ExtraInputType::GAID:
                $rules[] = 'regex:/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
                break;
            case ExtraInputType::TEXT:
                $rules[] = 'string';
                break;
        }

        return $rules;
    }
}
