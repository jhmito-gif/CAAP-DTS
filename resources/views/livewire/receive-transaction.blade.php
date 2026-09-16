{{-- Mark as Received: the receiving office records its own reference ID. --}}
<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-receive-transaction-modal.window="open = true; $nextTick(() => $refs.reference?.select())"
    x-on:close-receive-transaction-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="receiveTransactionModalLabel"
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

            <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <div class="min-w-0">
                <h5 id="receiveTransactionModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Mark as Received
                </h5>

                @if ($transaction)
                    <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $transaction->record->reference }} &middot; {{ $transaction->office }} &rarr; {{ $transaction->destination }}
                    </p>
                @endif
            </div>

        </div>

        <div class="px-6 py-4">

            <label for="receivedReference" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                Your reference ID <span class="text-red-500 dark:text-red-400">*</span>
            </label>

            <input
                type="text"
                id="receivedReference"
                x-ref="reference"
                wire:model="receivedReference"
                wire:keydown.enter="receive"
                autocomplete="off"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm font-semibold text-gray-800 dark:text-gray-100 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >

            @error('receivedReference')
                <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                @if ($reusedReference)
                    Your office already gave this document a number, so the same one is reused &mdash; one reference ID per office per document. Replace it only if you need a different one.
                @else
                    Suggested from {{ $transaction?->destination ?? 'your office' }}'s next number. Replace it if your office uses its own format (e.g. PD-07867). It is printed on the RAS.
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
                wire:click="receive"
                wire:loading.attr="disabled"
                wire:target="receive"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span wire:loading.remove wire:target="receive">Mark as Received</span>
                <span wire:loading wire:target="receive">Saving...</span>
            </button>

        </div>

    </div>
</div>
