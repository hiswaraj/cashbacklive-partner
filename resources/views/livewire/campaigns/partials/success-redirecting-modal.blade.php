<div
    class="bg-opacity-50 fixed inset-0 flex items-center justify-center bg-black/50 p-8"
>
    <div
        class="relative overflow-hidden rounded-xl border-2 border-yellow-200 bg-linear-to-br from-sky-700 to-green-500 p-8"
    >
        <div class="mb-6 text-center text-white">
            <h2 class="mb-2 text-3xl font-bold">Redirecting you</h2>
            <p class="text-lg">
                Please wait while we take you to your destination
            </p>
        </div>

        <div class="relative flex h-32 items-center justify-between">
            <!-- Source Icon -->
            <div
                class="z-10 flex animate-pulse items-center justify-center rounded-full bg-white shadow-lg"
            >
                <img
                    src="{{ asset('img/app-logo.webp') }}"
                    alt="Logo"
                    class="m-4 h-20 w-20 text-purple-600"
                />
            </div>

            <!-- Moving Arrow -->
            <div
                class="absolute top-1/2 left-1/2 flex w-3/4 -translate-x-1/2 -translate-y-1/2 transform justify-center"
            >
                <svg
                    class="animate-move-right drop-shadow-glow h-12 w-12 text-white filter"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                    ></path>
                </svg>
            </div>

            <!-- Destination Icon -->
            <div
                class="z-10 flex animate-pulse items-center justify-center rounded-full bg-white shadow-lg"
            >
                <img
                    src="{{ asset('storage/' . $campaign->logo_path) }}"
                    alt="{{ $campaign->name }} Logo"
                    class="m-4 h-20 w-20 rounded-full text-purple-600"
                />
            </div>
        </div>

        <div id="particles" class="pointer-events-none absolute inset-0"></div>
    </div>
</div>
