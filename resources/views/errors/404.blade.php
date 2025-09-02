<x-layouts.app>
    <div
        class="flex min-h-screen grow flex-col items-center justify-center bg-linear-to-br from-green-300 to-sky-300 p-4"
    >
        <div class="rounded-xl border-white bg-white p-5 shadow-lg">
            <div
                class="w-full max-w-sm rounded-xl bg-linear-to-tr from-violet-600 to-sky-300 p-8 text-center text-white"
            >
                <h1 class="mb-4 text-3xl font-bold">
                    @if (isset($exception->getHeaders()['heading']))
                        {{ $exception->getHeaders()['heading'] }}
                    @else
                        {{ __('404 - Page Not Found') }}
                    @endif
                </h1>
                <p class="mb-6 text-xl">
                    @if (isset($exception->getHeaders()['subtext']))
                        {{ $exception->getHeaders()['subtext'] }}
                    @else
                        {{ __('The page you are looking for does not exist.') }}
                    @endif
                </p>
                @php
                    $showTelegram = isset($exception->getHeaders()['showTelegramOn404']) && $exception->getHeaders()['showTelegramOn404'];
                    $redirectTelegram = isset($exception->getHeaders()['isRedirectToTelegram']) && $exception->getHeaders()['isRedirectToTelegram'];
                    $telegramUrl = app(App\Settings\GeneralSettings::class)->contact_telegram;
                @endphp

                @if ($showTelegram)
                    <a
                        href="{{ $telegramUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block w-full rounded-lg bg-blue-600 px-6 py-3 text-center font-semibold text-white transition duration-300 hover:bg-blue-700"
                    >
                        Stay Updated on Telegram
                    </a>
                    <p class="mt-4 text-center text-sm text-gray-200">
                        We're working on bringing you an improved experience.
                        Please join telegram channel for updates.
                    </p>
                @endif

                @if ($redirectTelegram && ! empty($telegramUrl))
                    <script>
                        setTimeout(function () {
                            window.location.href = '{{ $telegramUrl }}';
                        }, 3000);
                    </script>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
