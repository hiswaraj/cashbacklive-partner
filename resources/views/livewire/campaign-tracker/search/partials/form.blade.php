<form wire:submit.prevent="submit" class="space-y-4" wire:recaptcha>
    <x-input name="upi" placeholder="Enter your UPI ID" required />

    @if (app(App\Settings\CaptchaSettings::class)->enable_captcha_in_tracker_page)
        @livewireRecaptcha(siteKey: app(App\Settings\CaptchaSettings::class)->site_key)
        @if ($errors->has('gRecaptchaResponse'))
            <div class="mb-2">
                <span class="text-sm text-red-500">
                    {{ $errors->first('gRecaptchaResponse') }}
                </span>
            </div>
        @endif
    @endif

    <!-- Generate Link Button -->
    <x-button type="submit">
        <span wire:loading.remove>TRACK NOW</span>
        <span wire:loading>SEARCHING...</span>
    </x-button>
</form>
