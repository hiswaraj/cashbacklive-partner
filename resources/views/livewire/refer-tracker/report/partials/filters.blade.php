<div class="mb-6 overflow-hidden rounded-xl bg-white shadow-lg sm:mb-8">
    <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- Search --}}
            <div class="relative col-span-1 sm:col-span-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by event label..."
                    class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:py-3 sm:text-base"
                />
                <svg
                    class="absolute top-2.5 left-3 h-4 w-4 text-gray-400 sm:top-3.5 sm:h-5 sm:w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    ></path>
                </svg>
            </div>
            {{-- Export Button --}}
            <button
                wire:click="export"
                class="flex w-full items-center justify-center space-x-2 rounded-lg border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none sm:py-3 sm:text-base"
            >
                <svg
                    class="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span wire:loading.remove wire:target="export">Export CSV</span>
                <span wire:loading wire:target="export">Exporting...</span>
            </button>
        </div>

        {{-- Date Range Buttons --}}
        <div class="mt-4 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
            @foreach (['all' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'last7' => 'Last 7 Days', 'last30' => 'Last 30 Days', 'thisMonth' => 'This Month', 'lastMonth' => 'Last Month', 'custom' => 'Custom'] as $value => $label)
                <button
                    wire:click="setDateRange('{{ $value }}')"
                    class="{{ $dateRange === $value ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} rounded-lg px-3 py-2 text-xs font-medium transition-colors sm:px-4 sm:text-sm"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Custom Date Range Picker --}}
        @if ($dateRange === 'custom')
            <div
                class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2"
                wire:key="custom-date-range"
            >
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        From Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="startDate"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 sm:text-base"
                    />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        To Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="endDate"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 sm:text-base"
                    />
                </div>
            </div>
        @endif
    </div>
</div>
