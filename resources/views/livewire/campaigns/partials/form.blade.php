@php
    use App\Enums\ExtraInputType;
@endphp

<form wire:submit.prevent="submit" class="space-y-2" wire:recaptcha>
    <!-- Custom Input 1 -->
    @if ($campaign->is_extra_input_1_active)
        <x-input
            name="extra_input_1"
            :placeholder="'Enter ' . $campaign->extra_input_1_label"
            :type="$campaign->extra_input_1_type->value"
            :required="$campaign->is_extra_input_1_required"
        />
    @endif

    <!-- UPI Field -->
    <x-input name="upi" placeholder="Enter your UPI ID" required />

    <!-- Custom Input 2 -->
    @if ($campaign->is_extra_input_2_active)
        <x-input
            name="extra_input_2"
            :placeholder="'Enter ' . $campaign->extra_input_2_label"
            :type="$campaign->extra_input_2_type->value"
            :required="$campaign->is_extra_input_2_required"
        />
    @endif

    <!-- Custom Input 3 -->
    @if ($campaign->is_extra_input_3_active)
        <x-input
            name="extra_input_3"
            :placeholder="'Enter ' . $campaign->extra_input_3_label"
            :type="$campaign->extra_input_3_type->value"
            :required="$campaign->is_extra_input_3_required"
        />
    @endif

    @if (app(App\Settings\CaptchaSettings::class)->enable_captcha_in_campaign_form)
        @livewireRecaptcha(siteKey: app(App\Settings\CaptchaSettings::class)->site_key)
        @if ($errors->has('gRecaptchaResponse'))
            <span class="text-xs text-red-500">
                {{ $errors->first('gRecaptchaResponse') }}
            </span>
        @endif
    @endif

    <!-- Submit Button -->
    <x-button type="submit">
        <span wire:loading.remove>SUBMIT</span>
        <span wire:loading>PROCESSING...</span>
    </x-button>
</form>
