@vite('resources/css/tailwind.css')
<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($this->getGlobalLinks() as $name => $link)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-200 hover:shadow-md">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ ucfirst(str_replace('_', ' ', $name)) }}
                </h3>
                <div class="mt-2 flex items-center space-x-2">
                    <a
                            href="{{ $link }}"
                            target="_blank"
                            class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 truncate"
                    >
                        {{ $link }}
                    </a>
                    <button
                            onclick="copyToClipboard('{{ $link }}')"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                            x-data="{ copied: false }"
                            x-on:click="copied = true; setTimeout(() => copied = false, 2000)"
                    >
                        <div class="relative">
                            <x-filament::icon
                                    icon="heroicon-m-clipboard-document"
                                    class="w-5 h-5"
                                    x-show="!copied"
                            />
                            <x-filament::icon
                                    icon="heroicon-m-check"
                                    class="w-5 h-5 text-success-500"
                                    x-show="copied"
                                    x-cloak
                            />
                        </div>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
      function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
          // Success feedback is handled by Alpine.js
        }).catch(err => {
          console.error('Failed to copy text: ', err);
        });
      }
    </script>
</x-filament-panels::page>