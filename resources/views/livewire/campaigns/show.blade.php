@php
    // Calculate the sum of referral amounts for events that are NOT splittable.
    // This is efficient because $campaign->events are already eager-loaded in your component.
    $nonSplittableReferAmountSum = $campaign->events->where('is_commission_split_allowed', false)->sum('refer_amount');
@endphp

{{-- Main container with a deep and vibrant violet-to-indigo gradient --}}
<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-gradient-to-br from-indigo-800 via-purple-800 to-indigo-800 p-4"
>
    @if ($campaign->is_direct_redirect)
        {{-- Ensure this modal uses the same dark, frosted glass theme --}}
        @include('livewire.campaigns.partials.success-redirecting-modal')

    @elseif ($displayState === 'form')
        {{--
            The Card is styled with a "frosted glass" effect: a semi-transparent, blurred background
            with a subtle border, sitting on top of the main gradient. Text is set to a light color.
            Ensure your x-card component merges these classes.
        --}}
        <x-card
            :show-telegram="$campaign->is_footer_telegram_enabled"
            class="rounded-2xl border border-slate-700 bg-slate-900/40 text-white shadow-xl backdrop-blur-lg"
        >
            <div class="mx-auto w-full max-w-md space-y-4">
                {{--
                    SUGGESTED CHANGES FOR PARTIALS & COMPONENTS:

                    - Header/Description/Terms: Use bright white for titles (text-white) and softer grays for paragraphs (text-slate-300).
                    - Form: Style inputs with a dark background (bg-slate-800/50) and fuchsia focus rings (focus:ring-fuchsia-500).
                    - Telegram Button (`x-telegram-button`): Style it to match the theme's primary action color.
                      Example: A solid fuchsia button: <button class="bg-fuchsia-600 hover:bg-fuchsia-500 ...">
                --}}

                @include('livewire.campaigns.partials.header')
                @include('livewire.campaigns.partials.form')

                <div class="text-center">
                    {{-- Link color updated to a vibrant fuchsia that pops against the dark background --}}
                    <a
                        href="{{ route('short.campaign-tracker.search', ['campaign' => $campaign]) }}"
                        target="_blank"
                        class="text-sm font-medium text-fuchsia-400 transition hover:text-fuchsia-300"
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
        {{-- Ensure this modal uses the same dark, frosted glass theme --}}
        @include('livewire.campaigns.partials.ip-limit-reached-modal')
    @elseif ($displayState === 'success')
        {{-- Ensure this modal uses the same dark, frosted glass theme --}}
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
