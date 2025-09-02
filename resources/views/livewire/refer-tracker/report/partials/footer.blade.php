<div
    class="mt-6 flex flex-col gap-4 rounded-xl bg-white p-4 shadow-lg sm:mt-8 sm:flex-row sm:items-center sm:justify-between sm:p-6"
>
    <a
        href="{{ route('refer-tracker.search', ['campaign' => $campaign->id]) }}"
        class="flex items-center justify-center space-x-2 text-blue-600 transition-colors hover:text-blue-700 sm:justify-start"
    >
        <svg
            class="h-4 w-4 sm:h-5 sm:w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
            ></path>
        </svg>
        <span class="text-sm font-medium sm:text-base">Back to Search</span>
    </a>

    <div class="flex items-center justify-center space-x-4">
        @if ($campaign->is_footer_telegram_enabled)
            <a
                href="{{ app(App\Settings\GeneralSettings::class)->contact_telegram }}"
                target="_blank"
                class="flex items-center space-x-2 text-blue-600 transition-colors hover:text-blue-700"
            >
                <img
                    src="{{ asset('img/ic-telegram.png') }}"
                    alt="Telegram"
                    class="h-5 w-5 sm:h-6 sm:w-6"
                />
                <span class="text-xs sm:text-sm">Support</span>
            </a>
        @endif

        <a
            href="mailto:{{ app(App\Settings\GeneralSettings::class)->contact_email }}"
            class="flex items-center space-x-2 text-blue-600 transition-colors hover:text-blue-700"
        >
            <img
                src="{{ asset('img/ic-email.png') }}"
                alt="Email"
                class="h-5 w-5 sm:h-6 sm:w-6"
            />
            <span class="text-xs sm:text-sm">Contact</span>
        </a>
    </div>
</div>
