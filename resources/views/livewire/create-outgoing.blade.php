
<!-- Send Transaction Modal -->
<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-send-modal.window="open = true"
    x-on:close-send-modal.window="open = false"
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


    <!-- Modal Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
        class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl"
    >

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">

            <div class="flex items-center gap-3">

                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg
                        class="size-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12
                               59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"
                        />
                    </svg>
                </div>

                <div>
                    <h5
                        class="text-lg font-semibold text-gray-900"
                        id="createRecordModalLabel"
                    >
                        Send Transaction
                    </h5>

                    <p class="text-xs text-gray-400">
                        Create and forward a new outgoing transaction
                    </p>
                </div>

            </div>


            <!-- Close -->
            <button
                type="button"
                @click="open = false"
                class="rounded-lg p-1.5 text-gray-400 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-600"
                aria-label="Close"
            >
                <svg
                    class="size-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

        </div>


        <!-- Success Message -->
        @if (session()->has('message'))
            <div
                x-data
                x-init="setTimeout(() => $el.remove(), 4000)"
                class="mx-6 mt-4 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm"
            >
                <div class="flex items-center gap-2">

                    <svg
                        class="size-4 shrink-0 text-emerald-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0
                               1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>

                    <span class="text-sm font-medium">
                        {{ session('message') }}
                    </span>

                </div>

                <button
                    type="button"
                    onclick="this.parentElement.remove()"
                    class="ml-4 text-emerald-600 transition-colors hover:text-emerald-800 focus:outline-none"
                    aria-label="Dismiss"
                >
                    <svg
                        class="size-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 8.586 4.293 2.879a1 1 0 0 0-1.414
                               1.414L8.586 10l-5.707 5.707a1 1 0
                               1 0 1.414 1.414L10 11.414l5.707
                               5.707a1 1 0 0 0 1.414-1.414L11.414
                               10l5.707-5.707a1 1 0 0
                               0-1.414-1.414L10 8.586Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>

            </div>
        @endif


        <!-- Body -->
        <div class="space-y-4 px-6 py-5">


            <!-- Destination Office -->
            <div>

                <label
                    for="office"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Destination Office
                    <span class="text-red-500">*</span>
                </label>

                <select
                    wire:model="office"
                    id="office"
                    class="
                        w-full rounded-lg border border-gray-300 bg-white
                        px-3 py-2.5 text-sm text-gray-700 shadow-sm
                        transition
                        focus:border-emerald-500
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                    "
                >
                    <option value="">
                        -- Select Office --
                    </option>

                    @foreach ($officeOptions as $name)
                        <option value="{{ $name }}">
                            {{ $name }}
                        </option>
                    @endforeach
                </select>

                @error('office')
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <svg
                            class="size-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18
                                   0 9 9 0 0 1 18 0Zm-9
                                   3.75h.008v.008H12v-.008Z"
                            />
                        </svg>

                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Subject -->
            <div>

                <label
                    for="subject"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Subject
                    <span class="text-red-500">*</span>
                </label>

                <textarea
                    wire:model="subject"
                    id="subject"
                    rows="3"
                    class="
                        w-full resize-none rounded-lg border border-gray-300
                        px-3 py-2.5 text-sm text-gray-700 shadow-sm
                        transition
                        placeholder:text-gray-400
                        focus:border-emerald-500
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                    "
                    placeholder="Enter transaction subject..."
                ></textarea>

                @error('subject')
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <svg
                            class="size-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18
                                   0 9 9 0 0 1 18 0Zm-9
                                   3.75h.008v.008H12v-.008Z"
                            />
                        </svg>

                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Status -->
            <div>

                <label
                    for="status"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Status
                    <span class="text-red-500">*</span>
                </label>

                <select
                    wire:model="status"
                    id="status"
                    class="
                        w-full rounded-lg border border-gray-300 bg-white
                        px-3 py-2.5 text-sm text-gray-700 shadow-sm
                        transition
                        focus:border-emerald-500
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                    "
                >
                    <option value="">
                        -- Select Status --
                    </option>

                    @foreach ($statusOptions as $name)
                        <option value="{{ $name }}">
                            {{ $name }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <svg
                            class="size-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18
                                   0 9 9 0 0 1 18 0Zm-9
                                   3.75h.008v.008H12v-.008Z"
                            />
                        </svg>

                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Remarks -->
            <div>

                <label
                    for="remarks"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Remarks
                </label>

                <textarea
                    wire:model="remarks"
                    id="remarks"
                    rows="3"
                    class="
                        w-full resize-none rounded-lg border border-gray-300
                        px-3 py-2.5 text-sm text-gray-700 shadow-sm
                        transition
                        placeholder:text-gray-400
                        focus:border-emerald-500
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                    "
                    placeholder="Enter remarks..."
                ></textarea>

                @error('remarks')
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600">
                        <svg
                            class="size-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18
                                   0 9 9 0 0 1 18 0Zm-9
                                   3.75h.008v.008H12v-.008Z"
                            />
                        </svg>

                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>


        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">

            <button
                type="button"
                @click="open = false"
                wire:loading.attr="disabled"
                wire:target="createRecord"
                class="
                    rounded-lg border border-gray-300 bg-white
                    px-4 py-2 text-sm font-medium text-gray-600
                    shadow-sm transition-colors duration-150
                    hover:bg-gray-50 hover:text-gray-800
                    disabled:cursor-not-allowed disabled:opacity-50
                "
            >
                Close
            </button>


            <button
                type="button"
                wire:click="createRecord"
                wire:loading.attr="disabled"
                wire:target="createRecord"
                class="
                    inline-flex items-center justify-center gap-2
                    rounded-lg bg-emerald-600 px-4 py-2
                    text-sm font-semibold text-white shadow-sm
                    transition-colors duration-150
                    hover:bg-emerald-700
                    focus:outline-none
                    focus:ring-2
                    focus:ring-emerald-500
                    focus:ring-offset-2
                    disabled:cursor-not-allowed
                    disabled:opacity-70
                "
            >

                <!-- Loading -->
                <svg
                    wire:loading
                    wire:target="createRecord"
                    class="size-4 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 0 1 8-8V0C5.373
                           0 0 5.373 0 12h4Z"
                    ></path>
                </svg>


                <!-- Normal icon -->
                <svg
                    wire:loading.remove
                    wire:target="createRecord"
                    class="size-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 12 3.269 3.125A59.769 59.769
                           0 0 1 21.485 12 59.768 59.768
                           0 0 1 3.27 20.875L5.999 12Zm0
                           0h7.5"
                    />
                </svg>


                <span wire:loading.remove wire:target="createRecord">
                    Send Transaction
                </span>

                <span wire:loading wire:target="createRecord">
                    Sending...
                </span>

            </button>

        </div>

    </div>
</div>
