<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="referrer" content="never" />

        <title>
            {{ $title ?? app(App\Settings\GeneralSettings::class)->site_name }}
            
        </title>

        <link
            rel="icon"
            type="image/png"
            sizes="50x50"
            href="{{ asset('favicon.png') }}"
        />

        <!-- Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        @if (! app(App\Settings\CaptchaSettings::class)->show_badge)
            <style>
                .grecaptcha-badge {
                    visibility: hidden;
                }
            </style>
        @endif
    </head>

    <body
        class="bg-gray-50"
        x-data="{ loading: false }"
        x-on:livewire:loading.window="loading = true"
        x-on:livewire:load.window="loading = false"
    >
        <!-- Loading indicator -->
        <div
            x-show="loading"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="bg-opacity-30 fixed inset-0 z-50 flex items-center justify-center bg-black"
        >
            <div
                class="flex items-center space-x-3 rounded-lg bg-white p-4 shadow-lg"
            >
                <svg
                    class="h-5 w-5 animate-spin text-blue-500"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <span class="font-medium text-gray-700">Loading...</span>
            </div>
        </div>

        <!-- Page Content -->
        {{ $slot }}

        @livewireScripts
    </body>
</html>
