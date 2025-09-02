<form
    x-show="!$wire.showReferralLinkModal"
    wire:submit.prevent="submit"
    class="space-y-2"
    wire:recaptcha
>
    <!-- Mobile -->
    <x-input
        name="mobile"
        placeholder="Enter Mobile Number"
        type="tel"
        :required="true"
    />

    <!-- UPI Field -->
    <x-input name="upi" placeholder="Enter your UPI ID" required />

    @if ($campaign->is_referer_telegram_allowed)
        <x-input
            name="telegramUrl"
            placeholder="Telegram URL (optional)"
            type="url"
        />
    @endif

    {{-- Commission Split Section --}}
    @if ($splittableEvents->isNotEmpty())
        <div
            class="rounded-lg border border-gray-200 p-4"
            x-data="{ showSplits: false }"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    Customize Referral Commission
                </h3>
                <button
                    type="button"
                    @click="showSplits = !showSplits"
                    class="text-sm font-medium text-blue-600 hover:text-blue-500"
                >
                    <span x-show="!showSplits">Customize</span>
                    <span x-show="showSplits" x-cloak>Hide</span>
                </button>
            </div>

            <div
                x-show="showSplits"
                x-transition
                x-cloak
                class="mt-4 space-y-6"
            >
                @foreach ($splittableEvents as $event)
                    @php
                        $totalCommission = $event->user_amount + $event->refer_amount;
                        $minRefererShare = $event->min_refer_commission;
                        $maxRefererShare = $event->max_refer_commission;
                    @endphp

                    <div
                        wire:key="split-event-{{ $event->id }}"
                        class="border-t border-gray-200 pt-4 first:border-t-0 first:pt-0"
                        x-data="{
                            total: {{ $totalCommission }},
                            min: {{ $minRefererShare }},
                            max: {{ $maxRefererShare }},
                            refererShare: @entangle('commissionSplits.' . $event->id).live,
                        }"
                    >
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $event->label }}
                        </label>

                        <div
                            class="mt-2 flex items-center gap-2 text-sm text-gray-600"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="h-5 w-5 text-yellow-500"
                            >
                                <path
                                    d="M10 2a.75.75 0 01.75.75v.518a3.75 3.75 0 013.232 4.025l.138.761a3.75 3.75 0 01-2.222 4.22l-1.13.41a.75.75 0 01-.94-.34l-.32-1.12a3.75 3.75 0 01-5.016 0l-.32 1.12a.75.75 0 01-.94.34l-1.13-.41a3.75 3.75 0 01-2.222-4.22l.138-.761A3.75 3.75 0 016.25 3.268V2.75A.75.75 0 0110 2zM8.5 7.25a.75.75 0 00-1.5 0v1.5h-1.5a.75.75 0 000 1.5h1.5v1.5a.75.75 0 001.5 0v-1.5h1.5a.75.75 0 000-1.5h-1.5v-1.5z"
                                ></path>
                            </svg>
                            <p>
                                Total Bonus to Share:
                                <span class="font-bold text-gray-900">
                                    ₹{{ $totalCommission }}
                                </span>
                            </p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Referrer Share -->
                            <div
                                class="space-y-2 rounded-lg border border-green-200 bg-green-50 p-3"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-medium text-green-800"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            d="M11 5a3 3 0 11-6 0 3 3 0 016 0zM8 8a5 5 0 100 10A5 5 0 008 8zM14.25 8.25a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5z"
                                        ></path>
                                    </svg>
                                    <span>You get</span>
                                </div>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"
                                    >
                                        ₹
                                    </span>
                                    <input
                                        type="number"
                                        x-model.lazy.number="refererShare"
                                        step="1"
                                        :min="min"
                                        :max="max"
                                        class="w-full rounded-md border-gray-300 pl-7 text-lg font-semibold shadow-sm focus:border-green-500 focus:ring-green-500"
                                    />
                                </div>
                            </div>

                            <!-- New User Share -->
                            <div
                                class="space-y-2 rounded-lg border border-blue-200 bg-blue-50 p-3"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-medium text-blue-800"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.095a1.23 1.23 0 00.41-1.412A9.957 9.957 0 0010 12c-2.31 0-4.438.784-6.131 2.095z"
                                        ></path>
                                    </svg>
                                    <span>New user gets</span>
                                </div>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"
                                    >
                                        ₹
                                    </span>
                                    <input
                                        type="number"
                                        :value="total - refererShare"
                                        @input.lazy="refererShare = total - parseInt($event.target.value || 0)"
                                        step="1"
                                        :min="total - max"
                                        :max="total - min"
                                        class="w-full rounded-md border-gray-300 pl-7 text-lg font-semibold shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Range Slider -->
                        <div class="mt-4">
                            <input
                                id="split-{{ $event->id }}"
                                type="range"
                                :min="min"
                                :max="max"
                                step="1"
                                x-model.number="refererShare"
                                class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-yellow-500"
                            />
                            <div
                                class="mt-2 flex justify-between text-xs text-gray-500"
                            >
                                <span x-text="`₹${min}`"></span>
                                <span x-text="`₹${max}`"></span>
                            </div>
                        </div>

                        @error('commissionSplits.' . $event->id)
                            <span class="text-xs text-red-500">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (app(App\Settings\CaptchaSettings::class)->enable_captcha_in_refer_form)
        @livewireRecaptcha(siteKey: app(App\Settings\CaptchaSettings::class)->site_key)
        @if ($errors->has('gRecaptchaResponse'))
            <div class="mb-2">
                <span class="text-sm text-red-500">
                    {{ $errors->first('gRecaptchaResponse') }}
                </span>
            </div>
        @endif
    @endif

    <!-- Submit Button -->
    <x-button type="submit">
        <span wire:loading.remove>REFER</span>
        <span wire:loading>PROCESSING...</span>
    </x-button>
</form>
