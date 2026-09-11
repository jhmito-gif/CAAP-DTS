
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
        class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
    >

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-4">

            <div class="flex items-center gap-3">

                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
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
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                        id="createRecordModalLabel"
                    >
                        Send Transaction
                    </h5>

                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Create and forward a new outgoing transaction
                    </p>
                </div>

            </div>


            <!-- Close -->
            <button
                type="button"
                @click="open = false"
                class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
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
                class="mx-6 mt-4 flex items-center justify-between rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-emerald-800 shadow-sm"
            >
                <div class="flex items-center gap-2">

                    <svg
                        class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400"
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
                    class="ml-4 text-emerald-600 dark:text-emerald-400 transition-colors hover:text-emerald-800 focus:outline-none"
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
        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">


            <!-- Routing: office + status -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            <!-- Destination Office -->
            <div>

                <label
                    for="office"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Destination Office
                    <span class="text-red-500 dark:text-red-400">*</span>
                </label>

                <select
                    wire:model="office"
                    id="office"
                    class="
                        w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800
                        px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm
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
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
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
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Status
                    <span class="text-red-500 dark:text-red-400">*</span>
                </label>

                <select
                    wire:model="status"
                    id="status"
                    class="
                        w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800
                        px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm
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
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
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


            <!-- Subject -->
            <div>

                <label
                    for="subject"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    Subject
                    <span class="text-red-500 dark:text-red-400">*</span>
                </label>

                <textarea
                    wire:model="subject"
                    id="subject"
                    rows="3"
                    class="
                        w-full resize-none rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800
                        px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm
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
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
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
            <x-remarks-field model="remarks" label="Remarks" :rows="3" />


            <!-- Attachments -->
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Attachments
                    <span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span>
                </label>

                <label
                    for="outgoing-attachments"
                    class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 px-4 py-5 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40 dark:hover:border-emerald-600 dark:hover:bg-emerald-900/20"
                >
                    <svg class="size-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>

                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        Click to upload files
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        PDF, Word, Excel or images &middot; up to 10&nbsp;MB each
                    </span>

                    <input
                        id="outgoing-attachments"
                        type="file"
                        wire:model="attachments"
                        multiple
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        class="hidden"
                    >
                </label>

                {{-- Uploading indicator --}}
                <div wire:loading wire:target="attachments" class="mt-2 flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                    <svg class="size-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Uploading&hellip;
                </div>

                {{-- Selected files --}}
                @if (! empty($attachments))
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($attachments as $index => $file)
                            <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-900/30 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ strtoupper(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'FILE') }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $file->getClientOriginalName() }}
                                        </p>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                            {{ $file->getSize() < 1024 ? $file->getSize() . ' B' : round($file->getSize() / 1024, $file->getSize() >= 1048576 ? 0 : 1) . ($file->getSize() >= 1048576 ? ' MB' : ' KB') }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeAttachment({{ $index }})"
                                    class="shrink-0 rounded-md p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                    aria-label="Remove file"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @error('attachments.*')
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>


            <!-- Confidential lock -->
            <label
                for="outgoing-confidential"
                class="flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3 transition
                    {{ $isConfidential
                        ? 'border-rose-400 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/30 ring-1 ring-rose-300 dark:ring-rose-800'
                        : 'border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 hover:border-rose-300 dark:hover:border-rose-700' }}"
            >
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg {{ $isConfidential ? 'bg-rose-600 text-white' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Mark as Confidential</span>
                        @if ($isConfidential)
                            <span class="inline-flex items-center rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-white">Locked</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Locks this record and its files. Only tagged personnel and authorised viewers will see the subject, remarks and attachments &mdash; the routing chain sees only that a confidential record exists.
                    </p>
                </div>

                {{-- Toggle switch --}}
                <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $isConfidential ? 'bg-rose-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <input
                        type="checkbox"
                        id="outgoing-confidential"
                        wire:model.live="isConfidential"
                        class="peer sr-only"
                    >
                    <span class="inline-block size-4 translate-x-1 rounded-full bg-white shadow transition peer-checked:translate-x-6"></span>
                </span>
            </label>


            <!-- Confidential viewers (only when locked) -->
            @if ($isConfidential)
                {{-- Access token: cleared viewers must enter this to open the record --}}
                <div class="rounded-lg border border-rose-300 dark:border-rose-700 bg-rose-50/60 dark:bg-rose-900/20 p-4">

                    <div class="mb-2 flex items-center gap-2">
                        <svg class="size-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H9v1.5H7.5v1.5H6a1.5 1.5 0 0 1-1.5-1.5v-1.629c0-.398.158-.78.44-1.062l5.616-5.615a6 6 0 1 1 8.909-4.494Z" />
                        </svg>
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Access token <span class="text-rose-500">*</span></p>
                    </div>

                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Cleared viewers must enter this token each time they open the record's slip or files. Share it only with them.
                    </p>

                    <input
                        type="text"
                        wire:model="confidentialToken"
                        placeholder="e.g. a passphrase you share with viewers"
                        autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                    >

                    @error('confidentialToken')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg border border-rose-300 dark:border-rose-700 bg-rose-50/60 dark:bg-rose-900/20 p-4">

                    <div class="mb-2 flex items-center gap-2">
                        <svg class="size-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Authorised viewers</p>
                        @if (count($viewers) > 0)
                            <span class="rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ count($viewers) }} selected</span>
                        @endif
                    </div>

                    <p class="mb-2.5 text-xs text-gray-500 dark:text-gray-400">
                        Tag the people allowed to open this confidential record. Others in the routing chain will only see that it exists.
                    </p>

                    <select
                        wire:model.live="viewerOffice"
                        class="mb-2 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                    >
                        <option value="">-- Select office --</option>
                        @foreach ($officeOptions as $name)
                            <option value="{{ $name }}" @selected($viewerOffice === $name)>{{ $name }}</option>
                        @endforeach
                    </select>

                    @if (blank($viewerOffice))
                        <p class="rounded-lg border border-dashed border-rose-200 dark:border-rose-800 px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                            Choose an office to list its personnel.
                        </p>
                    @elseif ($viewerPersonnel->isEmpty())
                        <p class="rounded-lg border border-dashed border-rose-200 dark:border-rose-800 px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                            No personnel found under {{ $viewerOffice }}.
                        </p>
                    @else
                        <div class="max-h-44 divide-y divide-rose-100 dark:divide-rose-900/40 overflow-y-auto rounded-lg border border-rose-200 dark:border-rose-800 bg-white dark:!bg-gray-800">
                            @foreach ($viewerPersonnel as $person)
                                <label class="flex cursor-pointer items-center gap-3 px-3 py-2 transition hover:bg-rose-50/60 dark:hover:bg-rose-900/30">
                                    <input
                                        type="checkbox"
                                        value="{{ $person->id }}"
                                        wire:model="viewers"
                                        @checked(in_array((string) $person->id, array_map('strval', $viewers), true))
                                        class="size-4 shrink-0 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                    >
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/40 text-[10px] font-bold uppercase text-rose-700 dark:text-rose-300">
                                        {{ \Illuminate\Support\Str::substr($person->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $person->name }}</p>
                                        <p class="truncate text-[11px] text-gray-400 dark:text-gray-500">{{ $person->service ?: $person->office }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>


        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 px-6 py-4">

            <button
                type="button"
                @click="open = false"
                wire:loading.attr="disabled"
                wire:target="createRecord"
                class="
                    rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800
                    px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300
                    shadow-sm transition-colors duration-150
                    hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-100
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
