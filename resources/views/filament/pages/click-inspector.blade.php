<x-filament-panels::page>
    <form wire:submit.prevent="process" class="space-y-6">

        {{ $this->form }}

        <x-filament::button type="submit" form="process" style="margin-top: 12px">
            Process
        </x-filament::button>
    </form>

    {{ $this->table }}
</x-filament-panels::page>