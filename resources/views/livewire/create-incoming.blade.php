<!-- Send Modal -->
<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-modal.window="open = true"
    x-on:close-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    aria-labelledby="createRecordModalLabel"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
    ></div>

    <!-- Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
        class="relative w-full max-w-lg rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-4">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="createRecordModalLabel">Receive Transaction</h5>
            <button type="button" @click="open = false" class="rounded-lg p-1 text-gray-400 dark:text-gray-500 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @if (session()->has('message'))
            <div x-data x-init="setTimeout(() => $el.remove(), 4000)" class="mx-6 mt-4 flex items-center justify-between rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3 text-green-800 shadow-sm">
                <span class="text-sm font-medium">
                    {{ session('message') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" class="ml-4 text-green-600 dark:text-green-400 hover:text-green-800 focus:outline-none">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 8.586L4.293 2.879a1 1 0 00-1.414 1.414L8.586 10l-5.707 5.707a1 1 0 101.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- Body -->
        <div class="space-y-4 px-6 py-4">

            <!-- Office -->
            <div>
                <label for="office" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Origin Office</label>
                <select wire:model="office" id="office"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Select Office --</option>
                    @foreach ($officeOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('office') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="internalReferencePreview" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Internal Reference ID</label>
                <input id="internalReferencePreview"
                    type="text"
                    value="{{ $internalReferencePreview }}"
                    readonly
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-2 font-semibold text-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="originReference" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Origin Reference ID</label>
                <input wire:model="originReference" id="originReference"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Reference number from origin office">
                @error('originReference') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <!-- Subject -->
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Subject</label>
                <textarea wire:model="subject" rows="3"
                    class="w-full resize-none rounded-lg border-gray-300 dark:border-gray-700 px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Enter subject"></textarea>
                @error('subject') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                <select wire:model="status" id="status"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Select Status --</option>
                    @foreach ($statusOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('status') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            <!-- Remarks -->
            <x-remarks-field model="remarks" label="Remarks" :rows="3" accent="blue" />

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 px-6 py-4">
            <button type="button" @click="open = false"
                class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700">
                Close
            </button>
            <button type="button"
                wire:click="createRecord"
                wire:loading.attr="disabled"
                wire:target="createRecord"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70">
                <svg wire:loading wire:target="createRecord" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Create
            </button>
        </div>
    </div>
</div>
