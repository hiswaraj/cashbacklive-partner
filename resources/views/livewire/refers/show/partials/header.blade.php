<div class="flex flex-col items-center pt-6">
    <div
        class="flex items-center justify-center rounded-full border-4 border-gray-200 p-1"
    >
        <img
            src="{{ asset('storage/' . $campaign->logo_path) }}"
            alt="{{ $campaign->name }} Logo"
            class="h-24 w-24 rounded-full"
        />
    </div>
    <h2 class="mt-4 text-2xl font-bold">{{ $campaign->name }}</h2>
    <h2 class="text-1xl mt-1 text-center text-gray-500">
        Create Link -> Share -> Earn Rewards Worth
        <p
            class="text-xl font-bold text-red-500"
            x-data="{
                // Entangle the commissionSplits object with Livewire
                commissionSplits: @entangle('commissionSplits').live,

                // Store the static sum of non-splittable events
                nonSplittableSum: {{ $nonSplittableReferAmountSum }},

                // Create a getter that calculates the dynamic total
                get totalReferAmount() {
                    // Sum the values of the splittable events from the commissionSplits object
                    let splittableSum = Object.values(this.commissionSplits).reduce(
                        (sum, value) => sum + Number(value || 0),
                        0,
                    )
                    // Add the non-splittable sum to the dynamic splittable sum
                    return this.nonSplittableSum + splittableSum
                },
            }"
        >
            {{-- Use x-text to display the dynamic total from our Alpine component --}}
            ₹
            <span x-text="totalReferAmount" />
        </p>
    </h2>
</div>
