@php
    use App\Enums\ReferralPolicy;
@endphp

<div class="mx-auto max-w-[742px]">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        @foreach ($campaigns as $campaign)
            <div
                class="camp-card drop-shadow-6 rounded-30 border-gradient-primary-1 px-3 py-4 text-center"
            >
                <div class="mb-4">
                    <span
                        class="inline-block rounded-full bg-gradient-to-r from-red-400 to-red-300 px-4 py-1 text-sm font-medium text-white"
                    >
                        @if ($campaign->is_active)
                            Live
                        @else
                                Soon
                        @endif
                    </span>
                </div>
                <div
                    class="drop-shadow-6 mb-4 inline-block rounded-md bg-gradient-to-b from-[rgba(255,255,255,1)] to-[rgba(220,255,255,0.6)] p-2 md:min-w-[130px]"
                >
                    <img
                        class="mx-auto max-h-10"
                        src="{{ asset('storage/' . $campaign->logo_path) }}"
                        alt="store logo"
                    />
                </div>
                <div class="mb-4">
                    <p
                        class="h-14 overflow-hidden text-sm font-medium text-white/80 md:text-lg"
                    >
                        {{ $campaign->name }}
                        <b>(₹{{ $campaign->totalUserAmount() }})</b>
                    </p>
                </div>
                <div>
                    {{-- If referrals are open, show "Share & Earn" and link to the referral generation page. --}}

                    @if ($campaign->is_active && $campaign->referral_policy === ReferralPolicy::OPEN)
                        <a
                            href="{{ route('refer.show', ['campaign' => $campaign->id]) }}"
                            class="font-title text-dark from-orange to-yellow transition-3 inline-flex transform items-center justify-center space-x-3 rounded-full bg-gradient-to-b px-4 py-2 text-sm font-bold hover:bg-gradient-to-t sm:px-8 md:text-base"
                        >
                            <span>
                                Share & Earn
                                ₹{{ $campaign->totalReferAmount() }}
                            </span>
                        </a>
                    @elseif ($campaign->is_active)
                        {{-- Otherwise, if the campaign is active, link to the direct campaign page. --}}
                        <a
                            href="{{ route('campaign.show', ['campaign_or_refer_id' => $campaign->id]) }}"
                            class="font-title text-dark from-orange to-yellow transition-3 inline-flex transform items-center justify-center space-x-3 rounded-full bg-gradient-to-b px-4 py-2 text-sm font-bold hover:bg-gradient-to-t sm:px-8 md:text-base"
                        >
                            <span>Claim Now</span>
                        </a>
                    @else
                        {{-- If the campaign is not active, show a disabled "Over" button. --}}
                        <a
                            href="#"
                            class="font-title text-dark from-orange to-yellow transition-3 inline-flex transform items-center justify-center space-x-3 rounded-full bg-gradient-to-b px-4 py-2 text-sm font-bold hover:bg-gradient-to-t sm:px-8 md:text-base"
                            style="
                                pointer-events: none;
                                cursor: not-allowed;
                                opacity: 0.5;
                            "
                        >
                            <span>Over</span>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
