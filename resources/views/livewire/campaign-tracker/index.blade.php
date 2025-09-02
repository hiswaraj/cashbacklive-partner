<div
    class="flex min-h-screen grow flex-col items-center justify-center bg-linear-to-br from-green-300 to-sky-300 p-4"
>
    <x-card>
        <div class="mx-auto w-full max-w-md space-y-4">
            @include('livewire.campaign-tracker.index.partials.header')
            <x-select-campaign
                name="selectedCampaignId"
                :campaigns="$campaigns"
                :selectedCampaignId="$selectedCampaignId"
                total-amount-type="refer"
            />
        </div>
    </x-card>
</div>
