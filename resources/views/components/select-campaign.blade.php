<label
    for="campaign-select"
    class="mt-4 mb-2 block text-sm font-medium text-gray-700"
>
    Select Campaign
</label>
<select
    id="campaign-select"
    wire:model.live="selectedCampaignId"
    @class([
        'mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500 focus:outline-none sm:text-sm',
        'border-red-500' => $errors->has('selectedCampaignId'),
    ])
>
    <option value="">Select a campaign</option>
    @foreach ($campaigns as $campaignOption)
        <option value="{{ $campaignOption->id }}">
            {{ $campaignOption->name }}
        </option>
    @endforeach
</select>
@error('selectedCampaignId')
    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
@enderror
