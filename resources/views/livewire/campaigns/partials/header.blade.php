<div class="flex flex-col items-center pt-6">
    <div class="flex items-center justify-center border-4 border-blue-500">
        <img
            src="{{ asset('img/app-logo-full.jpg') }}"
            alt="Logo"
            class="h-24 w-98 rounded-full"
        />
    </div>
    <h2 class="mt-4 text-2xl font-bold">{{ $campaign->name }}</h2>
    <h2 class="text-1xl mx-6 mt-1 text-center text-gray-500">
        {!! $this->subtitle() !!}
    </h2>
</div>
