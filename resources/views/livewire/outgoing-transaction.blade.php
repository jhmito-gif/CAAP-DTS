<div class="min-h-screen bg-gray-50/70 dark:bg-gray-900">

    <section class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- ========================================================= --}}
            {{-- SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}
            @if (session()->has('message'))
                <div
                    class="mb-5 flex items-center justify-between rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 shadow-sm"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:text-emerald-400"
                        >
                            <svg
                                class="size-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-emerald-800">
                                {{ session('message') }}
                            </p>

                            <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                Transaction updated successfully.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        onclick="this.parentElement.remove()"
                        class="rounded-lg p-1.5 text-emerald-500 dark:text-emerald-400 transition hover:bg-emerald-100 hover:text-emerald-700 dark:hover:text-emerald-300"
                    >
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            @endif


            {{-- ========================================================= --}}
            {{-- RECORD HEADER --}}
            {{-- ========================================================= --}}
            <div class="mb-7 grid gap-6 xl:grid-cols-2 xl:items-start">
                <div class="min-w-0">
            <div
                class="overflow-hidden rounded-t-lg border border-b-0 border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800"
            >

                {{-- Top Accent --}}
                <div
                    class="h-1 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500"
                ></div>


                <div class="p-4 sm:p-5">

                    {{-- ========================================================= --}}
                    {{-- IDENTITY + ACTION ICONS --}}
                    {{-- ========================================================= --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">


                        {{-- ===================================================== --}}
                        {{-- RECORD IDENTITY --}}
                        {{-- ===================================================== --}}
                        <div class="flex min-w-0 flex-1 items-start gap-3">

                            {{-- Main Icon --}}
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                            >
                                <svg
                                    class="size-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75A3.375 3.375 0 0011.25 3.375H6.75A2.25 2.25 0 004.5 6v12a2.25 2.25 0 002.25 2.25h10.5A2.25 2.25 0 0019.5 18v-3.75z"
                                    />
                                </svg>
                            </div>


                            <div class="min-w-0 flex-1">

                                <p
                                    class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500"
                                >
                                    Transaction Record
                                </p>

                                <h1
                                    class="break-all text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl"
                                >
                                    {{ $record->reference ?? 'N/A' }}
                                </h1>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    View transaction details and routing history
                                </p>

                            </div>

                        </div>



                        {{-- ===================================================== --}}
                        {{-- ACTION ICONS --}}
                        {{-- ===================================================== --}}
                        <div class="flex shrink-0 flex-wrap items-center gap-2">


                            {{-- Send --}}
                            @if ($showSendButton)

                                <button
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sendModal"
                                    aria-label="Send this record to another office"
                                    class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-blue-600 hover:bg-blue-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
                                >

                                    <svg
                                        class="size-[18px]"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 12 3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5"
                                        />
                                    </svg>

                                    <span
                                        class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                    >
                                        Send
                                    </span>

                                </button>

                            @endif


                            {{-- Mark as Received --}}
                            @if ($receivableTransaction)

                                <button
                                    type="button"
                                    wire:click="markAsReceived({{ $receivableTransaction->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markAsReceived({{ $receivableTransaction->id }})"
                                    aria-label="Mark this record as received"
                                    class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-emerald-600 hover:bg-emerald-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm disabled:cursor-not-allowed disabled:opacity-60"
                                >

                                    <svg
                                        wire:loading.remove
                                        wire:target="markAsReceived({{ $receivableTransaction->id }})"
                                        class="size-[18px]"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>

                                    <svg
                                        wire:loading
                                        wire:target="markAsReceived({{ $receivableTransaction->id }})"
                                        class="size-[18px] animate-spin"
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
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        ></path>
                                    </svg>

                                    <span
                                        class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                    >
                                        <span wire:loading.remove wire:target="markAsReceived({{ $receivableTransaction->id }})">
                                            Mark as Received
                                        </span>

                                        <span wire:loading wire:target="markAsReceived({{ $receivableTransaction->id }})">
                                            Updating...
                                        </span>
                                    </span>

                                </button>

                            @endif


                            {{-- Urgent toggle --}}
                            <form
                                action="{{ route('records.toggle-urgent', $record->id) }}"
                                method="POST"
                                class="inline-flex"
                            >
                                @csrf
                                @method('PATCH')


                                @if ($record->is_urgent)

                                    <button
                                        type="submit"
                                        onclick="return confirm('Remove urgent status from this record?')"
                                        aria-label="Remove the urgent flag from this record"
                                        class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-amber-500 hover:bg-amber-500 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
                                    >

                                        <svg
                                            class="size-[18px]"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9.143 17.082a24.248 24.248 0 003.844.148m-3.844-.148a23.856 23.856 0 01-5.455-1.31 8.964 8.964 0 002.3-5.542m3.155 6.852a3 3 0 005.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 003.536-1.003A8.967 8.967 0 0118 9.75V9A6 6 0 006.53 6.53m10.245 10.245L6.53 6.53M3 3l3.53 3.53"
                                            />
                                        </svg>

                                        <span
                                            class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                        >
                                            Remove Urgent
                                        </span>

                                    </button>

                                @else

                                    <button
                                        type="submit"
                                        onclick="return confirm('Mark this record as urgent?')"
                                        aria-label="Mark this record as urgent"
                                        class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-red-600 hover:bg-red-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
                                    >

                                        <svg
                                            class="size-[18px]"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                            />
                                        </svg>

                                        <span
                                            class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                        >
                                            Mark Urgent
                                        </span>

                                    </button>

                                @endif

                            </form>


                            {{-- Tag People --}}
                            @livewire('tag-people', ['recordId' => $record->id], key('tag-people-' . $record->id))


                            {{-- Print --}}
                            <a
                                href="{{ route('records-pdf', $record->id) }}"
                                target="_blank"
                                rel="noopener"
                                aria-label="Open the printable Routing Action Slip"
                                class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-500 dark:text-gray-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-gray-900 hover:bg-gray-900 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
                            >

                                <svg
                                    class="size-[18px]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z"
                                    />
                                </svg>

                                <span
                                    class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                >
                                    Print RAS
                                </span>

                            </a>

                        </div>

                    </div>



                    {{-- ========================================================= --}}
                    {{-- RECORD DETAILS --}}
                    {{-- Full card width, so references are never clipped. --}}
                    {{-- ========================================================= --}}
                    <div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4">

                        <dl class="grid gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">


                            {{-- Origin Office --}}
                            <div class="min-w-0 border-l-2 border-gray-200 dark:border-gray-700 pl-3">

                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">

                                    <svg
                                        class="size-3.5 shrink-0"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"
                                        />
                                    </svg>

                                    Origin Office

                                </dt>

                                <dd class="mt-1 break-words text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $record->origin ?: '—' }}
                                </dd>

                            </div>


                            {{-- Tracking Reference --}}
                            <div class="min-w-0 border-l-2 border-blue-300 dark:border-blue-700 pl-3">

                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-blue-400 dark:text-blue-500">

                                    <svg
                                        class="size-3.5 shrink-0"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4.5 6.75h15m-15 5.25h15m-15 5.25h9"
                                        />
                                    </svg>

                                    Tracking Reference

                                </dt>

                                <dd class="mt-1 break-all text-sm font-bold text-blue-700 dark:text-blue-300">
                                    {{ $record->reference ?: '—' }}
                                </dd>

                            </div>


                            {{-- Origin Reference --}}
                            <div class="min-w-0 border-l-2 border-indigo-300 dark:border-indigo-700 pl-3">

                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-indigo-400 dark:text-indigo-500">

                                    <svg
                                        class="size-3.5 shrink-0"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>

                                    Origin Reference

                                </dt>

                                <dd class="mt-1 break-all text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                    {{ $record->origin_reference ?: '—' }}
                                </dd>

                            </div>

                        </dl>


                        {{-- Subject --}}
                        <div class="mt-3 min-w-0 border-l-2 border-gray-200 dark:border-gray-700 pl-3">

                            <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">

                                <svg
                                    class="size-3.5 shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25H12"
                                    />
                                </svg>

                                Subject

                            </dt>

                            <dd class="mt-1 break-words text-sm font-medium leading-5 text-gray-800 dark:text-gray-100">
                                {{ $record->subject ?: '—' }}
                            </dd>

                        </div>


                        {{-- Tagged Personnel --}}
                        <div class="mt-3 min-w-0 border-l-2 border-violet-300 dark:border-violet-700 pl-3">

                            <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-violet-400 dark:text-violet-500">

                                <svg
                                    class="size-3.5 shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                                    />
                                </svg>

                                Tagged Personnel

                            </dt>

                            <dd class="mt-1.5">

                                @if ($record->taggedUsers->isEmpty())

                                    <span class="text-sm text-gray-400 dark:text-gray-500">
                                        No one tagged yet
                                    </span>

                                @else

                                    <div class="flex flex-wrap gap-1.5">

                                        @foreach ($record->taggedUsers as $tagged)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/30 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300">

                                                <span class="flex size-4 items-center justify-center rounded-full bg-violet-200 text-[9px] font-bold uppercase text-violet-800">
                                                    {{ \Illuminate\Support\Str::substr($tagged->name, 0, 1) }}
                                                </span>

                                                {{ $tagged->name }}

                                                <span class="font-medium text-violet-400 dark:text-violet-500">
                                                    {{ $tagged->pivot->office ?: $tagged->office }}
                                                </span>

                                            </span>
                                        @endforeach

                                    </div>

                                @endif

                            </dd>

                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- TRANSACTION HISTORY --}}
            {{-- ========================================================= --}}
            <div
                class="overflow-hidden rounded-b-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800"
            >

                {{-- Section Header --}}
                <div
                    class="border-b border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-3 sm:px-5"
                >
                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                Transaction History
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                Routing and receiving activity for this record
                            </p>
                        </div>


                        <span
                            class="shrink-0 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            {{ $transactions->count() }}
                        </span>

                    </div>
                </div>


                {{-- Timeline --}}
                <div class="px-4 sm:px-5">

                    @forelse ($transactions as $transact)

                        @php
                            $isReceived = filled($transact->date_recieved);
                        @endphp


                        <div
                            class="group relative border-b border-gray-100 dark:border-gray-800 py-4 pl-8 last:border-b-0"
                        >

                            {{-- Vertical Line --}}
                            @if (!$loop->last)
                                <div
                                    class="absolute left-[9px] top-8 h-[calc(100%-1rem)] w-px bg-gray-200 dark:bg-gray-700"
                                ></div>
                            @endif


                            {{-- Timeline Icon --}}
                            <div
                                class="
                                    absolute left-0 top-4 z-10
                                    flex size-5 items-center justify-center rounded-full
                                    border-2 border-white

                                    {{ $isReceived
                                        ? 'bg-emerald-100 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-amber-100 text-amber-700 dark:text-amber-300'
                                    }}
                                "
                            >
                                @if ($isReceived)

                                    <svg
                                        class="size-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>

                                @else

                                    <svg
                                        class="size-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>

                                @endif
                            </div>


                            {{-- Transaction Card --}}
                            <div
                                class="min-w-0 bg-white dark:!bg-gray-800"
                            >

                                {{-- Transaction Header --}}
                                <div
                                    class="pb-3"
                                >

                                    <div
                                        class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                                    >

                                        <div class="min-w-0">

                                            <div
                                                class="mb-1.5 flex flex-wrap items-center gap-2"
                                            >

                                                <span
                                                    class="
                                                        inline-flex items-center gap-1.5 rounded-full
                                                        px-2.5 py-1 text-[11px] font-semibold

                                                        {{ $isReceived
                                                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                                        }}
                                                    "
                                                >
                                                    <span
                                                        class="
                                                            size-1.5 rounded-full

                                                            {{ $isReceived
                                                                ? 'bg-emerald-500'
                                                                : 'bg-amber-500'
                                                            }}
                                                        "
                                                    ></span>

                                                    {{ $transact->status ?? 'N/A' }}
                                                </span>


                                                <span
                                                    class="
                                                        text-[11px] font-medium

                                                        {{ $isReceived
                                                            ? 'text-emerald-600 dark:text-emerald-400'
                                                            : 'text-amber-600 dark:text-amber-400'
                                                        }}
                                                    "
                                                >
                                                    {{ $isReceived ? 'Received' : 'Awaiting Receipt' }}
                                                </span>

                                            </div>


                                            <h3
                                                class="sr-only"
                                            >
                                                {{ $transact->status ?? 'Transaction Activity' }}
                                            </h3>

                                        </div>

                                        {{-- Logged Date --}}
                                        <div
                                            class="flex shrink-0 items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400"
                                        >
                                            <svg
                                                class="size-4 text-gray-400 dark:text-gray-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="2"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>

                                            <span>
                                                {{ $transact->created_at
                                                    ? $transact->created_at->format('M d, Y \a\t h:i A')
                                                    : 'No Date Logged'
                                                }}
                                            </span>
                                        </div>

                                    </div>

                                </div>


                                <div class="space-y-3">

                                    {{-- ================================================= --}}
                                    {{-- ROUTING --}}
                                    {{-- ================================================= --}}
                                    <div>

                                        <p
                                            class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                        >
                                            Routing
                                        </p>


                                        <div
                                            class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center"
                                        >

                                            {{-- Sender --}}
                                            <div
                                                class="min-w-0 py-1"
                                            >
                                                <p
                                                    class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                                >
                                                    From
                                                </p>


                                                <div class="flex items-center gap-3">

                                                    <div
                                                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] font-bold uppercase text-gray-700 dark:text-gray-200"
                                                    >
                                                        {{ strtoupper(substr($transact->office ?? '?', 0, 1)) }}
                                                    </div>


                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                        >
                                                            {{ $transact->office ?? 'N/A' }}
                                                        </p>

                                                        <p
                                                            class="text-[11px] text-gray-400 dark:text-gray-500"
                                                        >
                                                            Sending office
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>


                                            {{-- Arrow --}}
                                            <div class="hidden sm:flex">

                                                <div
                                                    class="flex size-6 items-center justify-center text-gray-400 dark:text-gray-500"
                                                >
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
                                                            d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3"
                                                        />
                                                    </svg>
                                                </div>

                                            </div>


                                            {{-- Destination --}}
                                            <div
                                                class="min-w-0 py-1"
                                            >
                                                <p
                                                    class="mb-2 text-[10px] font-bold uppercase tracking-wider text-blue-400 dark:text-blue-500"
                                                >
                                                    Destination
                                                </p>


                                                <div class="flex items-center gap-3">

                                                    <div
                                                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold uppercase text-blue-700 dark:text-blue-300"
                                                    >
                                                        {{ strtoupper(substr($transact->destination ?? '?', 0, 1)) }}
                                                    </div>


                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-sm font-semibold text-blue-800"
                                                        >
                                                            {{ $transact->destination ?? 'N/A' }}
                                                        </p>

                                                        <p
                                                            class="text-[11px] text-blue-500 dark:text-blue-400"
                                                        >
                                                            Receiving office
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>

                                    </div>


                                    {{-- ================================================= --}}
                                    {{-- REMARKS --}}
                                    {{-- ================================================= --}}
                                    <div
                                        class="border-l-2 border-gray-200 dark:border-gray-700 py-1 pl-3"
                                    >

                                        <div class="mb-3 flex items-center gap-2">

                                            <div
                                                class="hidden"
                                            >
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
                                                        d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0"
                                                    />
                                                </svg>
                                            </div>

                                            <span
                                                class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                                            >
                                                Remarks
                                            </span>

                                        </div>


                                        <p
                                            class="whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-200"
                                        >
                                            {{ filled($transact->remarks)
                                                ? $transact->remarks
                                                : 'No remarks were provided for this transaction.'
                                            }}
                                        </p>

                                    </div>


                                    {{-- ================================================= --}}
                                    {{-- RECEIVED --}}
                                    {{-- ================================================= --}}
                                    @if ($isReceived)

                                        <div
                                            class="border-l-2 border-emerald-400 bg-emerald-50/40 dark:bg-emerald-900/30 px-3 py-2"
                                        >

                                            <div
                                                class="mb-5 flex items-center gap-3"
                                            >

                                                <div
                                                    class="flex size-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:text-emerald-300"
                                                >
                                                    <svg
                                                        class="size-5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="2.5"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="m4.5 12.75 6 6 9-13.5"
                                                        />
                                                    </svg>
                                                </div>


                                                <div>
                                                    <p
                                                        class="text-sm font-bold text-emerald-800"
                                                    >
                                                        Transaction Received
                                                    </p>

                                                    <p
                                                        class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400"
                                                    >
                                                        Successfully acknowledged by the receiving office
                                                    </p>
                                                </div>

                                            </div>


                                            <div
                                                class="grid gap-2 sm:grid-cols-2"
                                            >

                                                {{-- Received Date --}}
                                                <div
                                                    class="py-1"
                                                >
                                                    <p
                                                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400"
                                                    >
                                                        Received Date
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm font-semibold text-emerald-900"
                                                    >
                                                        {{ \Carbon\Carbon::parse($transact->date_recieved)->format('F j, Y g:i A') }}
                                                    </p>
                                                </div>


                                                {{-- Received By --}}
                                                <div
                                                    class="py-1"
                                                >
                                                    <p
                                                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400"
                                                    >
                                                        Received By
                                                    </p>


                                                    <div
                                                        class="mt-1.5 flex items-center gap-2"
                                                    >

                                                        <div
                                                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white dark:!bg-gray-800 text-[10px] font-bold uppercase text-emerald-700 dark:text-emerald-300 shadow-sm"
                                                        >
                                                            {{ strtoupper(substr($transact->recieved_by ?? '?', 0, 1)) }}
                                                        </div>

                                                        <p
                                                            class="truncate text-sm font-semibold text-emerald-900"
                                                        >
                                                            {{ $transact->recieved_by ?? 'N/A' }}
                                                        </p>

                                                    </div>
                                                </div>

                                            </div>

                                        </div>


                                    {{-- ================================================= --}}
                                    {{-- PENDING --}}
                                    {{-- ================================================= --}}
                                    @else

                                        <div
                                            class="border-l-2 border-amber-400 bg-amber-50/40 dark:bg-amber-900/30 px-3 py-2"
                                        >

                                            <div
                                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                                            >

                                                <div
                                                    class="flex items-start gap-3"
                                                >

                                                    <div
                                                        class="flex size-7 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-700 dark:text-amber-300"
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
                                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                                            />
                                                        </svg>
                                                    </div>


                                                    <div>
                                                        <p
                                                            class="text-sm font-bold text-amber-800"
                                                        >
                                                            Awaiting Receipt
                                                        </p>

                                                        <p
                                                            class="mt-1 max-w-md text-xs leading-5 text-amber-700 dark:text-amber-300"
                                                        >
                                                            This transaction has not yet been acknowledged by the receiving office.
                                                        </p>
                                                    </div>

                                                </div>


                                            </div>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>


                    @empty

                        {{-- Empty State --}}
                        <div class="px-6 py-16 text-center">

                            <div
                                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500"
                            >
                                <svg
                                    class="size-7"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.6"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>

                            <h3 class="text-base font-bold text-gray-700 dark:text-gray-200">
                                No transaction history
                            </h3>

                            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                                Routing activities will appear here once available.
                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

                </div>

                <div class="min-w-0 xl:sticky xl:top-6">
                    @include('partials.ras-viewer', [
                        'record' => $record,
                        'rasTransactions' => $rasTransactions,
                        'heightClass' => 'h-[760px]',
                    ])
                </div>
            </div>

        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- SEND TRANSACTION MODAL --}}
    {{-- ========================================================= --}}
    <div
        wire:ignore.self
        class="modal fade"
        id="sendModal"
        tabindex="-1"
        aria-labelledby="sendModalLabel"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div
                class="modal-content overflow-hidden rounded-2xl border-0 bg-white dark:!bg-gray-800 shadow-2xl"
            >

                {{-- ================================================= --}}
                {{-- MODAL HEADER --}}
                {{-- ================================================= --}}
                <div
                    class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-5"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
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
                                    d="M6 12 3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5"
                                />
                            </svg>
                        </div>


                        <div>
                            <h2
                                id="sendModalLabel"
                                class="text-lg font-bold text-gray-900 dark:text-gray-100"
                            >
                                Send Transaction
                            </h2>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Forward this record to another office
                            </p>
                        </div>

                    </div>


                    <button
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        class="flex size-9 items-center justify-center rounded-lg text-gray-400 dark:text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                </div>


                {{-- ================================================= --}}
                {{-- MODAL SUCCESS MESSAGE --}}
                {{-- ================================================= --}}
                @if (session()->has('message'))

                    <div
                        class="mx-6 mt-5 flex items-center justify-between rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-emerald-800"
                    >

                        <div class="flex items-center gap-2">

                            <svg
                                class="size-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>

                            <span class="text-sm font-medium">
                                {{ session('message') }}
                            </span>

                        </div>


                        <button
                            type="button"
                            onclick="this.parentElement.remove()"
                            class="ml-3 text-emerald-600 dark:text-emerald-400 hover:text-emerald-800"
                        >
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
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                    </div>

                @endif


                {{-- ================================================= --}}
                {{-- MODAL BODY --}}
                {{-- ================================================= --}}
                <div class="space-y-5 px-6 py-5">

                    {{-- Destination Office --}}
                    <div>

                        <label
                            for="office"
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Destination Office
                            <span class="text-red-500 dark:text-red-400">*</span>
                        </label>


                        <select
                            wire:model.live="office"
                            id="office"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
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
                            <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Status --}}
                    <div>

                        <label
                            for="status"
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Status
                            <span class="text-red-500 dark:text-red-400">*</span>
                        </label>


                        <select
                            wire:model="status"
                            id="status"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
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
                            <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Remarks --}}
                    <div>

                        <div
                            class="mb-1.5 flex items-center justify-between"
                        >
                            <label
                                for="remarks"
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >
                                Remarks
                            </label>

                            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                Optional
                            </span>
                        </div>


                        <textarea
                            wire:model.live.debounce.300ms="remarks"
                            id="remarks"
                            rows="4"
                            class="w-full resize-none rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Add a short note or instruction..."
                        ></textarea>


                        @error('remarks')
                            <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    @include('partials.ras-movement-preview', [
                        'fromOffice' => Auth::user()->office,
                        'toOffice' => $office,
                        'status' => $status,
                        'remarks' => $remarks,
                    ])

                </div>


                {{-- ================================================= --}}
                {{-- MODAL FOOTER --}}
                {{-- ================================================= --}}
                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900 px-6 py-4"
                >

                    <button
                        id="modalCloseBtn"
                        type="button"
                        data-bs-dismiss="modal"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 shadow-sm transition hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-100"
                    >
                        Cancel
                    </button>


                    @if ($showSendButton)

                        <button
                            type="button"
                            wire:click="sendTransaction"
                            wire:loading.attr="disabled"
                            wire:target="sendTransaction"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >

                            {{-- Loading --}}
                            <svg
                                wire:loading
                                wire:target="sendTransaction"
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
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                ></path>
                            </svg>


                            {{-- Normal Icon --}}
                            <svg
                                wire:loading.remove
                                wire:target="sendTransaction"
                                class="size-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 12 3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5"
                                />
                            </svg>


                            <span
                                wire:loading.remove
                                wire:target="sendTransaction"
                            >
                                Send Transaction
                            </span>

                            <span
                                wire:loading
                                wire:target="sendTransaction"
                            >
                                Sending...
                            </span>

                        </button>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>
