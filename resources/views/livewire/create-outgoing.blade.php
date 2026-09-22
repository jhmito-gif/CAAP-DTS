
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


            <!-- Signatories (only when a PDF is attached, and signing is on) -->
            @if ($hasPdfAttachment && \App\Support\Modules::enabled(\App\Support\Modules::ESIGN))
                <div class="rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50/50 dark:bg-sky-900/20 p-4">

                    <div class="mb-2 flex items-center gap-2">
                        <svg class="size-4 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            Request signatures
                            <span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span>
                        </p>
                        @if (count($signers) > 0)
                            <span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ count($signers) }} selected</span>
                        @endif
                    </div>

                    <p class="mb-2.5 text-xs text-gray-500 dark:text-gray-400">
                        Each person picked is asked to sign every PDF attached. They are notified, can open this record, and sign with their signing PIN.
                    </p>

                    <select
                        wire:model.live="signerOffice"
                        aria-label="Signatory office"
                        class="mb-2 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                    >
                        <option value="">-- Select office --</option>
                        @foreach ($officeOptions as $name)
                            <option value="{{ $name }}" @selected($signerOffice === $name)>{{ $name }}</option>
                        @endforeach
                    </select>

                    @if (blank($signerOffice))
                        <p class="rounded-lg border border-dashed border-sky-200 dark:border-sky-800 px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                            Choose an office to list its personnel.
                        </p>
                    @elseif ($signerPersonnel->isEmpty())
                        <p class="rounded-lg border border-dashed border-sky-200 dark:border-sky-800 px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                            No personnel found under {{ $signerOffice }}.
                        </p>
                    @else
                        <div class="max-h-44 divide-y divide-sky-100 dark:divide-sky-900/40 overflow-y-auto rounded-lg border border-sky-200 dark:border-sky-800 bg-white dark:!bg-gray-800">
                            @foreach ($signerPersonnel as $person)
                                <label wire:key="signer-option-{{ $person->id }}" class="flex cursor-pointer items-center gap-3 px-3 py-2 transition hover:bg-sky-50/60 dark:hover:bg-sky-900/30">
                                    <input
                                        type="checkbox"
                                        value="{{ $person->id }}"
                                        wire:model.live="signers"
                                        @checked(in_array((string) $person->id, array_map('strval', $signers), true))
                                        class="size-4 shrink-0 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                    >
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/40 text-[10px] font-bold uppercase text-sky-700 dark:text-sky-300">
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

                    @error('signers.*')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    {{-- Where each signature goes --}}
                    @if (count($signers) > 0 && $pdfUploads->isNotEmpty())
                        @php
                            $needed = count($signers) * $pdfUploads->count();
                            $marks = collect($placements)->filter(fn ($boxes, $key) => count($boxes) > 0
                                && $pdfUploads->contains('key', explode('|', $key)[0]));
                            $markedCount = $marks->count();
                            $pagesMarked = $marks->sum(fn ($boxes) => count($boxes));
                        @endphp

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-sky-200 dark:border-sky-800 bg-white dark:!bg-gray-800 px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">Signature position</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $markedCount }} of {{ $needed }} marked
                                    @if ($pagesMarked > 0)
                                        &middot; {{ $pagesMarked }} {{ \Illuminate\Support\Str::plural('page', $pagesMarked) }} in all
                                    @endif
                                    &middot; unmarked ones are found in the document at signing
                                </p>
                            </div>

                            <button
                                type="button"
                                x-data
                                @click="$dispatch('open-placement-picker')"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-sky-300 dark:border-sky-700 bg-sky-50 dark:bg-sky-900/30 px-3 py-1.5 text-xs font-semibold text-sky-700 dark:text-sky-300 transition hover:bg-sky-600 hover:text-white"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                Mark on the document
                            </button>
                        </div>
                    @endif
                </div>
            @endif


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


    {{-- ================================================================= --}}
    {{-- Mark where each signature goes --}}
    {{-- ================================================================= --}}
    @if (count($signers) > 0 && $pdfUploads->isNotEmpty())
        <div
            {{-- Alpine owns this dialog; a Livewire re-render must not reset it
                 mid-marking. A changed key (different files or signatories)
                 still replaces it with fresh data. --}}
            wire:ignore
            wire:key="placement-picker-{{ md5($pdfUploads->pluck('key')->implode(',') . '|' . implode(',', $signers)) }}"
            x-data="placementPicker({
                files: @js($pdfUploads),
                signers: @js($chosenSigners->map(fn ($person) => ['id' => $person->id, 'name' => $person->name])->values()),
                marks: @js((object) $placements),
            })"
            x-on:open-placement-picker.window="open = true; $nextTick(() => render())"
            x-on:keydown.escape.window="open = false"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="placementPickerLabel"
        >
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="open = false"></div>

            <div @click.stop class="relative flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                    <div class="min-w-0">
                        <h3 id="placementPickerLabel" class="text-sm font-bold text-gray-900 dark:text-gray-100">Mark where the signature goes</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Pick a signatory, then click the page. Mark as many pages as the document needs; the signer can still adjust them.
                        </p>
                    </div>

                    <button type="button" @click="open = false" aria-label="Close" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-3 p-4 lg:flex-row">

                    {{-- Page --}}
                    <div class="flex min-h-0 min-w-0 flex-1 flex-col">

                        <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <template x-for="file in files" :key="file.key">
                                <button type="button" @click="selectFile(file.key)"
                                    :class="file.key === fileKey ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300'"
                                    class="max-w-[220px] truncate rounded-md px-2.5 py-1 font-semibold" x-text="file.name"></button>
                            </template>

                            <span class="ml-auto flex items-center gap-2">
                                <button type="button" @click="go(-1)" :disabled="pageNumber <= 1" class="rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 font-semibold disabled:opacity-40">Prev</button>
                                <span>Page <span x-text="pageNumber"></span> of <span x-text="pageCount || '…'"></span></span>
                                <button type="button" @click="go(1)" :disabled="pageNumber >= pageCount" class="rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 font-semibold disabled:opacity-40">Next</button>
                            </span>
                        </div>

                        <div wire:ignore x-ref="stage" class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-gray-100 dark:bg-gray-900 p-4">
                            <p x-show="loading" class="py-20 text-center text-sm text-gray-500">Loading document…</p>
                            <p x-show="error" x-cloak x-text="error" class="py-20 text-center text-sm text-red-600"></p>

                            <div x-show="! loading && ! error" class="relative mx-auto" :style="`width: ${canvasWidth}px`">
                                <canvas x-ref="canvas" @click="place($event)" class="block cursor-crosshair bg-white shadow"></canvas>

                                <div
                                    x-show="boxVisible"
                                    x-cloak
                                    :style="boxStyle"
                                    @pointerdown.prevent="startDrag($event, 'move')"
                                    class="absolute flex cursor-move select-none items-center justify-center rounded border-2 border-dashed border-sky-500 bg-sky-50/50 text-[10px] font-semibold text-sky-800"
                                >
                                    <span x-text="(signers.find(s => s.id === signerId)?.name ?? '') + ' · p' + pageNumber"></span>
                                    <span @pointerdown.prevent.stop="startDrag($event, 'resize')" class="absolute -bottom-1.5 -right-1.5 size-3 cursor-se-resize rounded-sm bg-sky-600"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Signatories --}}
                    <div class="w-full shrink-0 lg:w-56">
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Signatories</p>

                        <div class="divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <template x-for="person in signers" :key="person.id">
                                <button type="button" @click="signerId = person.id"
                                    :class="person.id === signerId ? 'bg-sky-50 dark:bg-sky-900/30' : ''"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left">
                                    <span class="min-w-0">
                                        <span class="block truncate text-xs font-semibold text-gray-800 dark:text-gray-100" x-text="person.name"></span>
                                        <span class="block text-[10px]" :class="markedFor(person.id) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'"
                                            x-text="markedFor(person.id)
                                                ? markedFor(person.id) + (markedFor(person.id) > 1 ? ' pages marked' : ' page marked')
                                                : 'not marked'"></span>
                                    </span>
                                    <span x-show="markedFor(person.id)" class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                </button>
                            </template>
                        </div>

                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                            <span x-show="markedPages.length === 0">No page marked for this signatory yet.</span>
                            <span x-show="markedPages.length > 0" x-cloak>
                                Marked on page<span x-show="markedPages.length > 1">s</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="markedPages.join(', ')"></span>
                            </span>
                        </p>

                        <div class="mt-2 space-y-1.5">
                            <button type="button" x-show="mark && pageCount > 1" x-cloak @click="applyToAllPages()"
                                class="w-full rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/30 px-3 py-1.5 text-[11px] font-semibold text-sky-700 dark:text-sky-300 hover:bg-sky-600 hover:text-white">
                                Same spot on every page
                            </button>

                            <button type="button" x-show="mark" x-cloak @click="clearPage()"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Clear this page
                            </button>

                            <button type="button" x-show="boxes.length > 1" x-cloak @click="clearAll()"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Clear all pages
                            </button>
                        </div>

                        <p class="mt-3 text-[11px] leading-5 text-gray-500 dark:text-gray-400">
                            Unmarked signatories get their spot found in the document when they sign.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 dark:border-gray-700 px-5 py-3">
                    <button type="button" @click="open = false" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                        Done
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
