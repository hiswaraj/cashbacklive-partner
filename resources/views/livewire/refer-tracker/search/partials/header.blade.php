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

    <h2 class="text-1xl mt-1 text-center text-gray-500">Referral Tracker</h2>
</div>
