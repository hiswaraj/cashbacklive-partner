<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-linear-to-br from-green-300 to-sky-300 p-4"
>
    @if ($campaign->is_direct_redirect)
        @include('livewire.campaigns.partials.success-redirecting-modal')
    @elseif ($displayState === 'form')
        <x-card :show-telegram="$campaign->is_footer_telegram_enabled">
            <div class="mx-auto w-full max-w-md space-y-4">
                @include('livewire.campaigns.partials.header')
                @include('livewire.campaigns.partials.form')

                <div class="text-center">
                    <a
                        href="{{ route('short.campaign-tracker.search', ['campaign' => $campaign]) }}"
                        target="_blank"
                        class="text-sm font-medium text-blue-600 hover:text-blue-700"
                    >
                        Already submitted? Track your status here.
                    </a>
                </div>

                @if ($campaign->is_referer_telegram_allowed && $refer != null && $refer->telegram_url != null)
                    <x-telegram-button :url="$refer->telegram_url" />
                @endif

                @include('livewire.campaigns.partials.campaign-description')
                @include('livewire.campaigns.partials.campaign-terms')
            </div>
        </x-card>
    @elseif ($displayState === 'ip_limit_reached')
        @include('livewire.campaigns.partials.ip-limit-reached-modal')
    @elseif ($displayState === 'success')
        @include('livewire.campaigns.partials.success-redirecting-modal')
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('redirect', (url) => {
                setTimeout(() => {
                    window.location.href = url;
                }, 1000);
            });
        });
    </script>
</div>
