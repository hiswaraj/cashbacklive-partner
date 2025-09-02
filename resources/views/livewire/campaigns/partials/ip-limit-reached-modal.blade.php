<div
    class="bg-opacity-50 fixed inset-0 flex items-center justify-center bg-black/50 p-8"
>
    <div class="rounded-lg bg-red-50 p-6 text-center">
        <div class="mb-4 flex justify-center">
            <svg
                class="h-16 w-16 text-red-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                ></path>
            </svg>
        </div>

        <h2 class="mb-2 text-2xl font-bold text-red-600">IP Limit Reached</h2>

        <p class="mb-4 text-gray-700">
            You have reached the maximum number of submissions allowed from your
            current IP address.
        </p>

        <div class="mb-6 rounded-lg bg-blue-50 p-4 text-left">
            <h3 class="mb-2 font-semibold text-blue-700">
                How to change your IP address:
            </h3>
            <ol class="ml-4 list-decimal text-gray-700">
                <li class="mb-1">Turn on Airplane Mode on your device</li>
                <li class="mb-1">Wait for 10-15 seconds</li>
                <li class="mb-1">Turn off Airplane Mode</li>
                <li class="mb-1">
                    Wait for your device to reconnect to the network
                </li>
                <li>Refresh this page</li>
            </ol>
        </div>

        <button
            onclick="window.location.reload()"
            class="inline-flex items-center rounded-md bg-blue-500 px-4 py-2 text-white transition hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
        >
            <svg
                class="mr-2 h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                ></path>
            </svg>
            Refresh Page
        </button>
    </div>
</div>
