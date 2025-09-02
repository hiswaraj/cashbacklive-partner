@php
    // Calculate the sum of referral amounts for events that are NOT splittable.
    // This is efficient because $campaign->events are already eager-loaded in your component.
    $nonSplittableReferAmountSum = $campaign->events->where('is_commission_split_allowed', false)->sum('refer_amount');
@endphp

{{-- Main container with a deep and vibrant violet-to-indigo gradient --}}
<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-gradient-to-br from-indigo-800 via-purple-800 to-indigo-800 p-4"
>
    {{--
        The Card is styled with a "frosted glass" effect: a semi-transparent, blurred background
        with a subtle border, sitting on top of the main gradient. Text is set to a light color for readability.
        Ensure your x-card component merges these classes.
    --}}
    <x-card
        :show-telegram="$campaign->is_footer_telegram_enabled"
        class="rounded-2xl border border-slate-700 bg-slate-900/40 text-white shadow-xl backdrop-blur-lg"
    >
        <div class="mx-auto w-full max-w-md space-y-4">
            {{--
                SUGGESTED CHANGES FOR PARTIALS TO COMPLETE THE THEME:

                - Header (`partials.header`):
                  Use bright white for the main title and a softer gray for subtitles.
                  Example:
                  <h1 class="text-3xl font-bold text-white">Refer & Earn</h1>
                  <p class="text-slate-300 mt-2">Invite your friends to our campaign.</p>

                - Form (`partials.form`):
                  - Labels: <label class="text-sm font-medium text-slate-300">Your Email</label>
                  - Inputs: <input class="w-full rounded-md border-slate-600 bg-slate-800/50 text-slate-200 placeholder-slate-400 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                  - Button: <button class="w-full rounded-md bg-fuchsia-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-fuchsia-500">Get Your Link</button>

                - Terms (`partials.terms`):
                  Use a muted, subtle gray for the terms text.
                  Example: <p class="text-xs text-slate-400">By participating, you agree to our Terms...</p>
            --}}

            @include('livewire.refers.show.partials.header')
            @include('livewire.refers.show.partials.form')

            <div class="text-center">
                {{-- Link color updated to a vibrant fuchsia that pops against the dark background --}}
                <a
                    href="{{ route('short.refer-tracker.search', ['campaign' => $campaign]) }}"
                    target="_blank"
                    class="text-sm font-medium text-fuchsia-400 transition hover:text-fuchsia-300"
                >
                    Want to track your referrals? Click here.
                </a>
            </div>

            @include('livewire.refers.show.partials.terms')
        </div>
    </x-card>

    @if ($showReferralLinkModal)
        {{-- Success Modal: Ensure this partial also uses the same dark, frosted glass theme --}}
        @include('livewire.refers.show.partials.success-modal')
    @endif
</div>
