@props(['showTelegram' => true])

<div class="w-full max-w-md">
    <div class="overflow-hidden rounded-lg bg-white shadow-lg">
        <div class="px-6">
            {{ $slot }}
        </div>

        <div
            class="mt-4 flex justify-center space-x-2 border-t border-gray-200 p-4 text-center text-sm text-gray-500"
        >
            @if ($showTelegram)
                <a
                    href="{{ app(App\Settings\GeneralSettings::class)->contact_telegram }}"
                    class="text-blue-500 hover:text-blue-600"
                >
                    <img
                        class="h-10 w-10"
                        src="{{ asset('img/ic-telegram.png') }}"
                        alt="Telegram"
                    />
                </a>
            @endif

            <a
                href="mailto:{{ app(App\Settings\GeneralSettings::class)->contact_email }}"
                class="text-blue-500 hover:text-blue-600"
            >
                <img
                    class="h-10 w-10"
                    src="{{ asset('img/ic-email.png') }}"
                    alt="Email"
                />
            </a>
        </div>
    </div>
</div>
