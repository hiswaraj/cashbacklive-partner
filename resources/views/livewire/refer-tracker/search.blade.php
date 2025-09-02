<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-linear-to-br from-green-300 to-sky-300 p-4"
>
    <x-card :show-telegram="$campaign->is_footer_telegram_enabled">
        <div class="mx-auto w-full max-w-md space-y-4">
            @include('livewire.refer-tracker.search.partials.header')
            @include('livewire.refer-tracker.search.partials.form')
        </div>
    </x-card>
</div>
