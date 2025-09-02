<div
    class="mt-6 border-t-4 border-gray-200 pt-4"
    x-data="{ expanded: false }"
>
    <div
        @click="expanded = !expanded"
        class="flex cursor-pointer items-center justify-between"
    >
        <h3 class="text-xl font-bold text-red-500">Terms and Conditions:</h3>
        <svg
            class="h-6 w-6 transform text-gray-500 transition-transform"
            :class="{ 'rotate-180': expanded }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 9l-7 7-7-7"
            />
        </svg>
    </div>

    <div
        x-show="expanded"
        x-transition
        x-cloak
        class="prose prose-sm mt-2 text-gray-600 [&>ol]:list-decimal [&>ol]:px-4 [&>ul]:list-disc [&>ul]:px-4"
    >
        {!! $campaign->terms !!}
    </div>
</div>
