<div
    x-show="$wire.showReferralLinkModal"
    x-cloak
    x-transition
    class="bg-opacity-50 fixed inset-0 flex items-center justify-center bg-black/50 p-6"
>
    <div
        class="relative w-full max-w-md overflow-hidden rounded-xl border-2 border-yellow-200 bg-linear-to-br from-sky-700 to-green-500 p-4"
    >
        <h2 class="mb-4 text-xl font-bold text-white">Your Links</h2>

        <!-- Referral Link Section -->
        <div
            class="mb-4 rounded-lg bg-white/10 p-3"
            x-data="{ copied: false }"
        >
            <h3 class="mb-1 text-lg font-semibold text-yellow-200">
                Referral URL
            </h3>
            <div class="max-h-20 overflow-y-auto rounded bg-black/20 p-2">
                <p class="text-sm break-all text-gray-200">
                    {{ $referralLink }}
                </p>
            </div>
            <button
                @click="copied = true; navigator.clipboard.writeText('{{ $referralLink }}'); setTimeout(() => copied = false, 2000)"
                class="mt-2 flex w-full items-center justify-center rounded-md bg-green-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-400 focus:ring-2 focus:ring-green-300 focus:ring-offset-2 focus:outline-none"
            >
                <span x-show="!copied">Copy Referral URL</span>
                <span x-show="copied" class="flex items-center">
                    <svg
                        class="mr-1 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                    Copied!
                </span>
            </button>
        </div>

        <!-- Tracker Link Section -->
        <div
            class="mb-4 rounded-lg bg-white/10 p-3"
            x-data="{ copied: false }"
        >
            <h3 class="mb-1 text-lg font-semibold text-yellow-200">
                Tracker URL
            </h3>
            <div class="max-h-20 overflow-y-auto rounded bg-black/20 p-2">
                <p class="text-sm break-all text-gray-200">
                    {{ $trackerLink }}
                </p>
            </div>
            <button
                @click="copied = true; navigator.clipboard.writeText('{{ $trackerLink }}'); setTimeout(() => copied = false, 2000)"
                class="mt-2 flex w-full items-center justify-center rounded-md bg-yellow-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-yellow-400 focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2 focus:outline-none"
            >
                <span x-show="!copied">Copy Tracker URL</span>
                <span x-show="copied" class="flex items-center">
                    <svg
                        class="mr-1 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                    Copied!
                </span>
            </button>
        </div>

        <div class="flex justify-end">
            <button
                wire:click="closeSuccessModal"
                type="button"
                class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:outline-none"
            >
                Close
            </button>
        </div>
    </div>
</div>
