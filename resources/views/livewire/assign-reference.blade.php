{{-- Reference ID: the office records its own number for this document. --}}
<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-assign-reference-modal.window="open = true; $nextTick(() => $refs.reference?.select())"
    x-on:close-assign-reference-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="assignReferenceModalLabel"
>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition
        @click.stop
        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
    >

        <div class="flex items-start gap-4 px-6 pb-2 pt-6">

            <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
            </div>

            <div class="min-w-0">
                <h5 id="assignReferenceModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Reference ID
                </h5>

                @if ($record)
                    <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $record->reference }} &middot; {{ auth()->user()->office }}
                    </p>
                @endif
            </div>

        </div>

        <div class="px-6 py-4">

            <label for="assignedReference" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                Your office's reference ID <span class="text-red-500 dark:text-red-400">*</span>
            </label>

            <input
                type="text"
                id="assignedReference"
                x-ref="reference"
                wire:model="reference"
                wire:keydown.enter="save"
                autocomplete="off"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm font-semibold text-gray-800 dark:text-gray-100 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >

            @error('reference')
                <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                @if ($reused)
                    Your office already gave this document a number, so the same one is shown &mdash; one reference ID per office per document. Change it only if you need a different one.
                @else
                    Filled in from {{ auth()->user()->office }}'s next number. Replace it if your office uses its own format (e.g. PD-07867). You can set this before the document is received; it is printed on the RAS.
                @endif
            </p>

        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 px-6 py-4">

            <button
                type="button"
                @click="open = false"
                class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-100"
            >
                Cancel
            </button>

            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span wire:loading.remove wire:target="save">Save reference ID</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>

        </div>

    </div>
</div>
