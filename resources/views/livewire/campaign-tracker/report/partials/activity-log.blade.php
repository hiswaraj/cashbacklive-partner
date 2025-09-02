@php
    use Carbon\Carbon;
@endphp

<div class="space-y-4 sm:space-y-6">
    @foreach ($clicksByDate as $date => $dayClicks)
        <div
            class="overflow-hidden rounded-xl bg-white shadow-lg"
            wire:key="date-group-{{ $date }}"
        >
            <div
                class="flex flex-col gap-2 bg-linear-to-r from-blue-600 to-indigo-600 px-4 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <div class="flex items-center space-x-2">
                    <svg
                        class="h-4 w-4 shrink-0 sm:h-5 sm:w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        ></path>
                    </svg>
                    <span class="text-sm font-semibold sm:text-base">
                        {{ Carbon::parse($date)->format('F j, Y') }}
                    </span>
                </div>
                <span
                    class="rounded-full bg-white/20 px-3 py-1 text-xs sm:text-sm"
                >
                    {{ $dayClicks->count() }}
                    {{ \Illuminate\Support\Str::plural('lead', $dayClicks->count()) }}
                </span>
            </div>
            <div class="p-4 sm:p-6">
                <div class="space-y-3 sm:space-y-4">
                    @foreach ($dayClicks as $click)
                        <div
                            class="rounded-lg border border-gray-200 p-3 sm:p-4"
                            wire:key="click-{{ $click->id }}"
                        >
                            <div
                                class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <h4
                                    class="text-sm font-medium text-gray-900 sm:text-base"
                                >
                                    Lead Details
                                </h4>
                                <span class="text-xs text-gray-500 sm:text-sm">
                                    {{ $click->created_at->format('g:i A') }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @php
                                    $hasFoundACompletedEvent = false;
                                    $instructionShownForThisClick = false;
                                @endphp

                                @foreach ($allCampaignEvents as $event)
                                    @php
                                        // Use the pre-computed map for efficient O(1) lookup
                                        $conversion = $click->conversionsByEvent->get($event->id);
                                        $isCompleted = (bool) $conversion;
                                        $paymentStatus = null;
                                        if ($conversion && $event->is_instant_pay_user && $event->user_amount > 0) {
                                            $earning = $conversion->earnings->where('type', App\Enums\EarningType::USER)->first();
                                            $paymentStatus = $earning?->payout?->status->value ?? 'pending';
                                        }
                                        $calculatedAmounts = $conversion?->calculated_amounts;
                                    @endphp

                                    @if ($hasFoundACompletedEvent && ! $isCompleted && ! $instructionShownForThisClick)
                                        <div
                                            wire:key="next-step-{{ $click->id }}-{{ $event->id }}"
                                            class="my-2 flex items-start space-x-3 rounded-lg border-l-4 border-blue-400 bg-blue-50 p-3 text-sm"
                                        >
                                            <svg
                                                class="h-5 w-5 shrink-0 text-blue-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <div class="text-blue-800">
                                                <p class="font-semibold">
                                                    Next Step:
                                                </p>
                                                <p>
                                                    Please complete: "
                                                    <strong>
                                                        {{ $event->label }}
                                                    </strong>
                                                    ". After you've done it,
                                                    please wait for the status
                                                    to update here.
                                                </p>
                                            </div>
                                        </div>
                                        @php
                                            $instructionShownForThisClick = true;
                                        @endphp
                                    @endif

                                    <div
                                        class="{{ $isCompleted ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }} flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
                                        wire:key="event-{{ $click->id }}-{{ $event->id }}"
                                    >
                                        <div
                                            class="flex min-w-0 flex-1 items-center space-x-3"
                                        >
                                            <div
                                                class="{{ $isCompleted ? 'bg-green-500' : 'bg-gray-300' }} h-3 w-3 shrink-0 rounded-full"
                                            ></div>
                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-sm font-medium break-words text-gray-900"
                                                >
                                                    {{ $event->label }}
                                                </p>

                                                @if ($conversion)
                                                    <p
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Completed:
                                                        {{ $conversion->created_at->format('M j, g:i A') }}
                                                    </p>
                                                @else
                                                    <p
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Not completed
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($event->is_instant_pay_user && $event->user_amount > 0 && $isCompleted)
                                            <div
                                                class="flex flex-col items-end"
                                            >
                                                <span
                                                    class="{{ match ($paymentStatus) {'success' => 'bg-green-200 text-green-800','failed' => 'bg-red-200 text-red-800', default => 'bg-yellow-200 text-yellow-800',} }} rounded-full px-2 py-1 text-xs font-medium whitespace-nowrap"
                                                >
                                                    {{ ucfirst($paymentStatus) }}
                                                    ₹{{ $calculatedAmounts['user'] }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    @php
                                        if ($isCompleted) {
                                            $hasFoundACompletedEvent = true;
                                        }
                                    @endphp
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
