@props([
    'type' => 'button',
])
<button
    type="{{ $type }}"
    class="focus:ring-primary w-full rounded-md bg-yellow-400 px-4 py-2 text-sm font-bold text-white hover:bg-yellow-600 focus:ring-2 focus:ring-offset-2 focus:outline-none"
    wire:loading.attr="disabled"
    wire:loading.class="opacity-50 cursor-not-allowed"
>
    {{ $slot }}
</button>
