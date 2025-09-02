<div
    class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 p-2 sm:p-4"
    x-data
>
    <div class="mx-auto max-w-6xl">
        {{-- Header: Campaign info, UPI, and quick stats --}}
        @include('livewire.campaign-tracker.report.partials.header')

        {{-- Filters: Search and date range controls --}}
        @include('livewire.campaign-tracker.report.partials.filters')

        {{-- Main Content: Display stats and activity if results exist --}}
        @if ($hasResults)
            {{-- Detailed log of all clicks and their associated event statuses --}}
            @include('livewire.campaign-tracker.report.partials.activity-log')
        @else
            {{-- Message shown when no data matches the current filters --}}
            @include('livewire.campaign-tracker.report.partials.no-results')
        @endif

        {{-- Footer: Back link and contact info --}}
        @include('livewire.campaign-tracker.report.partials.footer')
    </div>
</div>
