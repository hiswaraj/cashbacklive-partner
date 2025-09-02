@php
    // Calculate the sum of referral amounts for events that are NOT splittable.
    // This is efficient because $campaign->events are already eager-loaded in your component.
    $nonSplittableReferAmountSum = $campaign->events->where('is_commission_split_allowed', false)->sum('refer_amount');
@endphp

<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-linear-to-br from-green-300 to-sky-300 p-4"
>
    <x-card :show-telegram="$campaign->is_footer_telegram_enabled">
        <div class="mx-auto w-full max-w-md space-y-4">
            @include('livewire.refers.show.partials.header')
            @include('livewire.refers.show.partials.form')
            <div class="text-center">
                <a
                    href="{{ route('short.refer-tracker.search', ['campaign' => $campaign]) }}"
                    target="_blank"
                    class="text-sm font-medium text-blue-600 hover:text-blue-700"
                >
                    Want to track your referrals? Click here.
                </a>
            </div>
            @include('livewire.refers.show.partials.terms')
        </div>
    </x-card>
    @if ($showReferralLinkModal)
        @include('livewire.refers.show.partials.success-modal')
    @endif
</div>
