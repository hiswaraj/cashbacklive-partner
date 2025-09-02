<div class="mb-6 overflow-hidden rounded-xl bg-white shadow-lg sm:mb-8">
    <div
        class="bg-linear-to-r from-blue-600 to-indigo-600 p-4 text-white sm:p-6"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div
                class="flex min-w-0 flex-1 items-center space-x-3 sm:space-x-4"
            >
                <div
                    class="h-12 w-12 shrink-0 overflow-hidden rounded-full border-4 border-white/20 sm:h-16 sm:w-16"
                >
                    <img
                        src="{{ asset('storage/' . $campaign->logo_path) }}"
                        alt="{{ $campaign->name }}"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-lg font-bold sm:text-2xl">
                        {{ $campaign->name }}
                    </h1>
                    <p class="text-xs text-blue-100 sm:text-sm">
                        Referral Performance Dashboard
                    </p>
                </div>
            </div>
            <div class="w-full shrink-0 sm:w-auto">
                <div
                    class="rounded-lg bg-white/10 px-3 py-2 text-center sm:px-4 sm:text-right"
                >
                    <p class="text-xs text-blue-100 sm:text-sm">Your UPI ID</p>
                    <p
                        class="font-mono text-sm font-semibold break-all sm:text-lg sm:break-normal"
                    >
                        {{ $refer->upi }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    @if ($hasResults)
        <div class="grid grid-cols-2 gap-3 p-4 sm:gap-4 sm:p-6">
            <div class="rounded-lg bg-blue-50 p-3 text-center sm:p-4">
                <div class="text-xl font-bold text-blue-600 sm:text-3xl">
                    {{ $totalClicks }}
                </div>
                <div class="text-xs text-gray-600 sm:text-sm">Total Clicks</div>
            </div>
            <div class="rounded-lg bg-yellow-50 p-3 text-center sm:p-4">
                <div class="text-xl font-bold text-yellow-600 sm:text-3xl">
                    ₹{{ number_format($totalEarnings, 2) }}
                </div>
                <div class="text-xs text-gray-600 sm:text-sm">
                    Referral Earnings
                </div>
            </div>
        </div>
    @endif
</div>
