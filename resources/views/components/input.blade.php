@props([
    'name',
    'placeholder',
    'type' => 'text',
    'required' => false,
    'live' => false,
])
<div>
    @php($placeholder = $required ? $placeholder . ' *' : $placeholder)
    <input
        @if ($live) wire:model.live="{{ $name }}" @else wire:model="{{ $name }}" @endif
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        @class([
            'w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-yellow-500 focus:outline-none',
            'border-red-500' => $errors->has($name),
        ])
        {{ $required ? 'required' : '' }}
    />

    @error($name)
        <span class="mt-2 text-xs text-red-500">
            {{ $message }}
        </span>
    @enderror
</div>
