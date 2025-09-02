<x-filament-panels::page>
    @vite('resources/css/tailwind.css')
    <form wire:submit="createRefer">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="createRefer"
            >
                Generate Links
            </x-filament::button>
        </div>
    </form>

    @if($results)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Generated Links
                </x-slot>
                <x-slot name="description">
                    Use these links for your partner. The copy buttons will confirm success.
                </x-slot>

                <div class="space-y-4">
                    {{-- Referral URL --}}
                    <div>
                        <label class="inline-block text-sm font-medium leading-6 text-gray-950 dark:text-white">Referral
                            URL</label>
                        <div class="mt-2 flex items-center gap-x-2">
                            <div class="flex-1">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" readonly :value="$results['referralLink']"/>
                                </x-filament::input.wrapper>
                            </div>
                            <button type="button" onclick="copyToClipboard('{{ $results['referralLink'] }}')"
                                    class="rounded-lg p-2 text-gray-500 transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700/50"
                                    x-data="{ copied: false }"
                                    x-on:click.stop="copied = true; setTimeout(() => copied = false, 2000)">
                                <div class="relative">
                                    <x-filament::icon icon="heroicon-o-clipboard" class="h-5 w-5" x-show="!copied"/>
                                    <x-filament::icon icon="heroicon-o-check" class="h-5 w-5 text-success-500"
                                                      x-show="copied" x-cloak/>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Tracker URL --}}
                    <div>
                        <label class="inline-block text-sm font-medium leading-6 text-gray-950 dark:text-white">Tracker
                            URL</label>
                        <div class="mt-2 flex items-center gap-x-2">
                            <div class="flex-1">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" readonly :value="$results['trackerLink']"/>
                                </x-filament::input.wrapper>
                            </div>
                            <button type="button" onclick="copyToClipboard('{{ $results['trackerLink'] }}')"
                                    class="rounded-lg p-2 text-gray-500 transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-700/50"
                                    x-data="{ copied: false }"
                                    x-on:click.stop="copied = true; setTimeout(() => copied = false, 2000)">
                                <div class="relative">
                                    <x-filament::icon icon="heroicon-o-clipboard" class="h-5 w-5" x-show="!copied"/>
                                    <x-filament::icon icon="heroicon-o-check" class="h-5 w-5 text-success-500"
                                                      x-show="copied" x-cloak/>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    </script>
</x-filament-panels::page>