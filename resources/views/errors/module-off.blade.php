<x-app-layout>
    <div class="mx-auto max-w-xl px-4 py-24 text-center sm:px-6">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
            </svg>
        </div>

        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $label }} is turned off</h1>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            An administrator has switched this part of the system off. Nothing has been deleted:
            it will all be here again when it is switched back on.
        </p>

        <a href="{{ route('dashboard') }}" class="mt-6 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
            Back to the dashboard
        </a>
    </div>
</x-app-layout>
