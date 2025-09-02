@php use App\Enums\ReferralPolicy; @endphp
@vite('resources/css/tailwind.css')
<div
        x-data="{
        copyToClipboard(text) {
            navigator.clipboard.writeText(text)
            alert('Copied to clipboard!')
        },
    }"
        class="py-6"
>
    <div class="space-y-4">
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-600">Campaign link</h3>
            <div class="flex items-center">
                @php
                    $campaignShowLink = route('short.campaign.show', ['campaign_or_refer_id' => $campaign->id]);
                @endphp
                <input
                        type="text"
                        value="{{ $campaignShowLink }}"
                        class="grow rounded-md border border-gray-300 bg-gray-200 px-3 py-2 leading-tight text-gray-700 focus:border-gray-500 focus:outline-none"
                        readonly
                />
                <button
                        x-on:click="copyToClipboard('{{ $campaignShowLink }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Copy
                </button>
                <button
                        x-on:click="window.open('{{ $campaignShowLink }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Open
                </button>
            </div>
        </div>

        @if ($campaign->referral_policy !== ReferralPolicy::DISABLED)
            <div>
                <h3 class="mb-2 text-sm font-medium text-gray-600">Refer link</h3>
                <div class="flex items-center">
                    @php
                        $referShowLink = route('short.refer.show', ['campaign' => $campaign->id]);
                    @endphp
                    <input
                            type="text"
                            value="{{ $referShowLink }}"
                            class="grow rounded-md border border-gray-300 bg-gray-200 px-3 py-2 leading-tight text-gray-700 focus:border-gray-500 focus:outline-none"
                            readonly
                    />
                    <button
                            x-on:click="copyToClipboard('{{ $referShowLink }}')"
                            class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                    >
                        Copy
                    </button>
                    <button
                            x-on:click="window.open('{{ $referShowLink }}')"
                            class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                    >
                        Open
                    </button>
                </div>
            </div>
        @endif

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-600">Webhook link</h3>
            <div class="flex items-center">
                @php
                    $webhookUrl = url(route('webhook.conversion')) . '?webhook_secret=' . $campaign->webhook_secret . '&click_id={click_id}&event={event}';
                @endphp
                <input
                        type="text"
                        value="{{ $webhookUrl }}"
                        class="grow rounded-md border border-gray-300 bg-gray-200 px-3 py-2 leading-tight text-gray-700 focus:border-gray-500 focus:outline-none"
                        readonly
                />
                <button
                        x-on:click="copyToClipboard('{{ $webhookUrl }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Copy
                </button>
                <button
                        x-on:click="window.open('{{ $webhookUrl }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Open
                </button>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-600">Lead Tracker link</h3>
            <div class="flex items-center">
                @php
                    // Use short.campaign-tracker.search for specific campaign tracker link
                    $campaignTrackerLink = route('short.campaign-tracker.search', ['campaign' => $campaign->id]);
                @endphp
                <input
                        type="text"
                        value="{{ $campaignTrackerLink }}"
                        class="grow rounded-md border border-gray-300 bg-gray-200 px-3 py-2 leading-tight text-gray-700 focus:border-gray-500 focus:outline-none"
                        readonly
                />
                <button
                        x-on:click="copyToClipboard('{{ $campaignTrackerLink }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Copy
                </button>
                <button
                        x-on:click="window.open('{{ $campaignTrackerLink }}')"
                        class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                >
                    Open
                </button>
            </div>
        </div>

        @if ($campaign->referral_policy !== ReferralPolicy::DISABLED)
            <div>
                <h3 class="mb-2 text-sm font-medium text-gray-600">Refer Tracker link</h3>
                <div class="flex items-center">
                    @php
                        // This is a general refer tracker link, not specific to a campaign ID in the path
                        $referTrackerSearchLink = route('short.refer-tracker.search', ['campaign' => $campaign->id]);
                    @endphp
                    <input
                            type="text"
                            value="{{ $referTrackerSearchLink }}"
                            class="grow rounded-md border border-gray-300 bg-gray-200 px-3 py-2 leading-tight text-gray-700 focus:border-gray-500 focus:outline-none"
                            readonly
                    />
                    <button
                            x-on:click="copyToClipboard('{{ $referTrackerSearchLink }}')"
                            class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                    >
                        Copy
                    </button>
                    <button
                            x-on:click="window.open('{{ $referTrackerSearchLink }}')"
                            class="ml-2 rounded-md bg-gray-200 px-3 py-2 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-300"
                    >
                        Open
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>