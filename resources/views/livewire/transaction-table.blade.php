<div class="min-h-screen bg-gray-50/70 dark:bg-gray-900">

    @php
        // Confidential record opened by a viewer not cleared for its details.
        $masked = $record && $record->isMaskedFor(auth()->user());
    @endphp

    <section class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- URGENT SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}
            @if (session()->has('urgent_message'))

                <div
                    id="urgentMessage"
                    class="mb-5 flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-3 shadow-sm"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="
                                flex size-9 shrink-0 items-center justify-center rounded-lg

                                {{ $record->is_urgent
                                    ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                    : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
                                }}
                            "
                        >
                            @if ($record->is_urgent)

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
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                    />
                                </svg>

                            @else

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

                            @endif
                        </div>


                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ session('urgent_message') }}
                            </p>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Transaction priority has been updated.
                            </p>
                        </div>

                    </div>


                    <button
                        type="button"
                        onclick="document.getElementById('urgentMessage')?.remove()"
                        class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
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
                class="
                    overflow-hidden rounded-lg border bg-white dark:!bg-gray-800

                    {{ $record->is_urgent
                        ? 'border-red-200 dark:border-red-800'
                        : 'border-gray-200 dark:border-gray-700'
                    }}
                "
            >

                {{-- Top accent --}}
                @if ($record->is_urgent)

                    <div class="h-1 bg-gradient-to-r from-red-600 via-red-500 to-orange-500"></div>

                @else

                    <div class="h-1 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500"></div>

                @endif


                <div class="p-4 sm:p-5">

                    {{-- ========================================================= --}}
                    {{-- IDENTITY + ACTION ICONS --}}
                    {{-- ========================================================= --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">


                        {{-- ===================================================== --}}
                        {{-- RECORD IDENTITY --}}
                        {{-- ===================================================== --}}
                        <div class="flex min-w-0 flex-1 items-start gap-3">

                            {{-- Main icon --}}
                            <div
                                class="
                                    flex size-10 shrink-0 items-center justify-center rounded-md

                                    {{ $record->is_urgent
                                        ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                        : 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                                    }}
                                "
                            >
                                @if ($record->is_urgent)

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
                                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                        />
                                    </svg>

                                @else

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

                                @endif
                            </div>


                            <div class="min-w-0 flex-1">

                                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                    Transaction Record
                                </p>


                                <div class="flex flex-wrap items-center gap-2.5">

                                    <h1 class="break-all text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
                                        {{ $record->reference }}
                                    </h1>


                                    {{-- Urgent Badge --}}
                                    @if ($record->is_urgent)

                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-red-700 dark:text-red-300"
                                        >

                                            <span class="relative flex size-2">

                                                <span
                                                    class="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-50"
                                                ></span>

                                                <span
                                                    class="relative inline-flex size-2 rounded-full bg-red-500"
                                                ></span>

                                            </span>

                                            Urgent

                                        </span>

                                    @endif


                                    {{-- Confidential Badge --}}
                                    @if ($record->is_confidential)

                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-300 dark:border-rose-700 bg-rose-600 px-3 py-1 text-[11px] font-extrabold uppercase tracking-widest text-white shadow-sm">
                                            <span class="relative flex size-2">
                                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-rose-200 opacity-60"></span>
                                                <span class="relative inline-flex size-2 rounded-full bg-white"></span>
                                            </span>
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Confidential
                                        </span>

                                    @endif

                                </div>


                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    View routing activity and transaction history
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
                                    id="openSendModal"
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
                                @if ($masked)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-rose-600 dark:text-rose-400">
                                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        Confidential — hidden from you
                                    </span>
                                @else
                                    {{ $record->subject ?: '—' }}
                                @endif
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



                    {{-- ========================================================= --}}
                    {{-- URGENT WARNING --}}
                    {{-- ========================================================= --}}
                    @if ($record->is_urgent)

                        <div class="mt-4 flex items-start gap-3 border-l-2 border-red-400 bg-red-50/50 dark:bg-red-900/30 px-3 py-2">

                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:text-red-400">

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
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                    />
                                </svg>

                            </div>


                            <div>

                                <p class="text-sm font-semibold text-red-800">
                                    Urgent transaction
                                </p>

                                <p class="mt-0.5 text-xs leading-5 text-red-600 dark:text-red-400">
                                    This record has been marked as urgent and requires priority processing.
                                </p>

                            </div>

                        </div>

                    @endif

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- HISTORY + DETAILS --}}
            {{-- ========================================================= --}}
            <div class="mt-4 grid gap-4">


                {{-- ===================================================== --}}
                {{-- LEFT SIDE: HISTORY --}}
                {{-- ===================================================== --}}
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800">

                    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-3">

                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    Transaction History
                                </h2>

                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    Select an activity to view details
                                </p>

                            </div>


                            <span class="rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $transactions->count() }}
                            </span>

                        </div>

                    </div>



                    <div
                        id="transactionHistoryList"
                        class="max-h-[440px] divide-y divide-gray-100 dark:divide-gray-800 overflow-y-auto"
                    >

                        @forelse ($transactions as $transact)

                            @php
                                $isReceived = filled($transact->date_recieved);
                            @endphp


                            <button
                                type="button"
                                class="transaction-history-item group flex w-full items-start gap-3 border-l-2 border-transparent px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"

                                data-id="{{ $transact->id }}"

                                data-status="{{ $transact->status }}"

                                data-office="{{ $transact->office }}"

                                data-destination="{{ $transact->destination }}"

                                data-remarks="{{ $masked ? 'Confidential — hidden from you' : $transact->remarks }}"

                                data-created="{{ $transact->created_at
                                    ? $transact->created_at->format('F j, Y g:i A')
                                    : 'No Date Logged'
                                }}"

                                data-received="{{ $isReceived ? '1' : '0' }}"

                                data-date-received="{{ $isReceived
                                    ? \Carbon\Carbon::parse($transact->date_recieved)->format('F j, Y g:i A')
                                    : ''
                                }}"

                                data-received-by="{{ $transact->recieved_by ?? '' }}"
                            >


                                {{-- State icon --}}
                                <div
                                    class="
                                        flex size-8 shrink-0 items-center justify-center rounded-full

                                        {{ $isReceived
                                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
                                            : 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                                        }}
                                    "
                                >

                                    @if ($isReceived)

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

                                    @else

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
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>

                                    @endif

                                </div>



                                {{-- Summary --}}
                                <div class="min-w-0 flex-1">

                                    <div class="mb-1.5">

                                        <span
                                            class="
                                                inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold

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

                                            {{ $transact->status }}

                                        </span>

                                    </div>



                                    <div class="flex min-w-0 items-center gap-1.5">

                                        <span class="max-w-[100px] truncate text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ $transact->office ?? 'N/A' }}
                                        </span>


                                        <svg
                                            class="size-3 shrink-0 text-gray-300 dark:text-gray-600"
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


                                        <span class="max-w-[110px] truncate text-sm font-semibold text-blue-600 dark:text-blue-400">
                                            {{ $transact->destination ?? 'N/A' }}
                                        </span>

                                    </div>


                                    <p class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $transact->created_at
                                            ? $transact->created_at->format('M d, Y \a\t h:i A')
                                            : 'No Date Logged'
                                        }}
                                    </p>

                                </div>



                                {{-- Arrow --}}
                                <div class="mt-2 flex size-7 shrink-0 items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 transition group-hover:bg-white group-hover:text-blue-500">

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
                                            d="m9 18 6-6-6-6"
                                        />
                                    </svg>

                                </div>

                            </button>


                        @empty

                            <div class="px-6 py-16 text-center">

                                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">

                                    <svg
                                        class="size-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>

                                </div>


                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    No transaction history
                                </p>

                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    Activities will appear here.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- RIGHT SIDE: DETAILS --}}
                {{-- ===================================================== --}}
                <div
                    id="transactionDetailPanel"
                    class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800"
                >

                    {{-- Default / empty state --}}
                    <div
                        id="transactionDetailEmpty"
                        class="flex min-h-40 flex-col items-center justify-center px-6 py-8 text-center"
                    >

                        <div class="mb-3 flex size-10 items-center justify-center rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-500 dark:text-blue-400">

                            <svg
                                class="size-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75A3.375 3.375 0 0011.25 3.375H6.75A2.25 2.25 0 004.5 6v12a2.25 2.25 0 002.25 2.25h10.5A2.25 2.25 0 0019.5 18v-3.75z"
                                />
                            </svg>

                        </div>


                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                            Select a transaction
                        </h3>


                        <p class="mt-2 max-w-md text-sm leading-6 text-gray-400 dark:text-gray-500">
                            Choose an activity from the transaction history to view its routing,
                            remarks and receiving information.
                        </p>

                    </div>



                    {{-- ================================================= --}}
                    {{-- ACTUAL TRANSACTION DETAILS --}}
                    {{-- ================================================= --}}
                    <div
                        id="transactionDetailContent"
                        class="hidden"
                    >

                        {{-- Header --}}
                        <div class="border-b border-gray-100 dark:border-gray-800 bg-white dark:!bg-gray-800 px-5 py-4">

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                <div>

                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        Transaction Details
                                    </p>


                                    <div class="flex flex-wrap items-center gap-2">

                                        <h3
                                            id="detailStatus"
                                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                                        ></h3>


                                        <span
                                            id="detailStatusBadge"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                        ></span>

                                    </div>

                                </div>


                                <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">

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
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>

                                    <span id="detailCreated"></span>

                                </div>

                            </div>

                        </div>



                        <div class="space-y-4 p-5">


                            {{-- ================================================= --}}
                            {{-- ROUTING --}}
                            {{-- ================================================= --}}
                            <div>

                                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Routing
                                </p>


                                <div class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">


                                    {{-- Sender --}}
                                    <div class="min-w-0 py-1">

                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                            From
                                        </p>


                                        <div class="flex items-center gap-3">

                                            <div
                                                id="detailOfficeInitial"
                                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] font-bold uppercase text-gray-700 dark:text-gray-200"
                                            ></div>


                                            <div class="min-w-0">

                                                <p
                                                    id="detailOffice"
                                                    class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                ></p>

                                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                                    Sending office
                                                </p>

                                            </div>

                                        </div>

                                    </div>



                                    {{-- Arrow --}}
                                    <div class="hidden sm:flex">

                                        <div class="flex size-6 items-center justify-center text-gray-400 dark:text-gray-500">

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
                                    <div class="min-w-0 py-1">

                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-blue-400 dark:text-blue-500">
                                            Destination
                                        </p>


                                        <div class="flex items-center gap-3">

                                            <div
                                                id="detailDestinationInitial"
                                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold uppercase text-blue-700 dark:text-blue-300"
                                            ></div>


                                            <div class="min-w-0">

                                                <p
                                                    id="detailDestination"
                                                    class="truncate text-sm font-semibold text-blue-800"
                                                ></p>

                                                <p class="text-[11px] text-blue-500 dark:text-blue-400">
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
                            <div class="border-l-2 border-gray-200 dark:border-gray-700 py-1 pl-3">

                                <div class="mb-3 flex items-center gap-2">

                                    <div class="hidden">

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


                                    <span class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                        Remarks
                                    </span>

                                </div>


                                <p
                                    id="detailRemarks"
                                    class="text-sm leading-6 text-gray-700 dark:text-gray-200"
                                ></p>

                            </div>



                            {{-- ================================================= --}}
                            {{-- RECEIVED --}}
                            {{-- ================================================= --}}
                            <div
                                id="detailReceivedBox"
                                class="hidden border-l-2 border-emerald-400 bg-emerald-50/40 dark:bg-emerald-900/30 px-3 py-2"
                            >

                                <div class="mb-5 flex items-center gap-3">

                                    <div class="flex size-7 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:text-emerald-300">

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

                                        <p class="text-sm font-bold text-emerald-800">
                                            Transaction Received
                                        </p>

                                        <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">
                                            Successfully acknowledged by the receiving office
                                        </p>

                                    </div>

                                </div>


                                <div class="grid gap-2 sm:grid-cols-2">

                                    <div class="rounded-lg bg-white/70 dark:bg-gray-800 p-3">

                                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400">
                                            Received Date
                                        </p>

                                        <p
                                            id="detailDateReceived"
                                            class="mt-1 text-sm font-semibold text-emerald-900"
                                        ></p>

                                    </div>


                                    <div class="rounded-lg bg-white/70 dark:bg-gray-800 p-3">

                                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 dark:text-emerald-400">
                                            Received By
                                        </p>


                                        <div class="mt-1.5 flex items-center gap-2">

                                            <div
                                                id="detailReceiverInitial"
                                                class="flex size-7 items-center justify-center rounded-full bg-white dark:!bg-gray-800 text-[10px] font-bold uppercase text-emerald-700 dark:text-emerald-300 shadow-sm"
                                            ></div>

                                            <p
                                                id="detailReceivedBy"
                                                class="text-sm font-semibold text-emerald-900"
                                            ></p>

                                        </div>

                                    </div>

                                </div>

                            </div>



                            {{-- ================================================= --}}
                            {{-- PENDING --}}
                            {{-- ================================================= --}}
                            <div
                                id="detailPendingBox"
                                class="hidden border-l-2 border-amber-400 bg-amber-50/40 dark:bg-amber-900/30 px-3 py-2"
                            >

                                <div class="flex items-start gap-3">

                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-700 dark:text-amber-300">

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

                                        <p class="text-sm font-bold text-amber-800">
                                            Awaiting Receipt
                                        </p>

                                        <p class="mt-1 max-w-md text-xs leading-5 text-amber-700 dark:text-amber-300">
                                            This transaction has not yet been acknowledged by the receiving office.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

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
        id="sendModal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sendModalLabel"
    >

        {{-- Backdrop --}}
        <div
            id="sendModalBackdrop"
            class="absolute inset-0 bg-gray-950/50 opacity-0 backdrop-blur-sm transition-opacity duration-200"
        ></div>



        {{-- Panel --}}
        <div
            id="sendModalPanel"
            class="relative w-full max-w-lg translate-y-3 scale-95 overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 opacity-0 shadow-2xl transition-all duration-200"
        >

            {{-- Header --}}
            <div class="border-b border-gray-100 dark:border-gray-800 px-6 py-5">

                <div class="flex items-center justify-between">

                    <div class="flex items-center gap-3">

                        <div class="flex size-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">

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
                        data-close-send-modal
                        class="rounded-lg p-2 text-gray-400 dark:text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
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

            </div>



            {{-- Livewire success --}}
            @if (session()->has('message'))

                <div
                    id="successMessage"
                    class="mx-6 mt-5 flex items-center justify-between rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-emerald-800"
                >

                    <span class="text-sm font-medium">
                        {{ session('message') }}
                    </span>


                    <button
                        type="button"
                        onclick="document.getElementById('successMessage')?.remove()"
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



            {{-- Body --}}
            <div class="space-y-5 px-6 py-5">


                {{-- Destination --}}
                <div>

                    <label
                        for="sendOffice"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Destination Office
                        <span class="text-red-500 dark:text-red-400">*</span>
                    </label>


                    <select
                        wire:model.live="office"
                        id="sendOffice"
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
                        for="sendStatus"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Status
                        <span class="text-red-500 dark:text-red-400">*</span>
                    </label>


                    <select
                        wire:model="status"
                        id="sendStatus"
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

                    <div class="mb-1.5 flex items-center justify-between">

                        <label
                            for="sendRemarks"
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
                        id="sendRemarks"
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



            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900 px-6 py-4">

                <button
                    type="button"
                    data-close-send-modal
                    class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-600 dark:text-gray-300 shadow-sm transition hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>


                @if ($showSendButton)

                    <button
                        type="button"
                        wire:click="sendTransaction"
                        wire:loading.attr="disabled"
                        wire:target="sendTransaction"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >

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



{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}
<script>
    /*
    |--------------------------------------------------------------------------
    | Transaction page
    |--------------------------------------------------------------------------
    |
    | Uses delegated listeners so the UI continues working after
    | Livewire updates the DOM.
    |
    */

    (() => {

        if (window.__transactionPageLoaded) {
            return;
        }

        window.__transactionPageLoaded = true;


        let selectedTransactionId = null;


        /*
        |--------------------------------------------------------------------------
        | Helpers
        |--------------------------------------------------------------------------
        */

        function firstLetter(value) {

            if (!value || !value.trim()) {
                return '?';
            }

            return value.trim().charAt(0).toUpperCase();

        }


        function getCurrentLivewireComponent(element) {

            if (!window.Livewire) {
                return null;
            }

            const root = element.closest('[wire\\:id]');

            if (!root) {
                return null;
            }

            return Livewire.find(
                root.getAttribute('wire:id')
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Transaction History
        |--------------------------------------------------------------------------
        */

        function selectTransaction(item) {

            const data = item.dataset;

            selectedTransactionId = data.id;


            /*
             * Clear selected history item.
             */
            document
                .querySelectorAll('.transaction-history-item')
                .forEach(historyItem => {

                    historyItem.classList.remove(
                        'border-blue-500',
                        'bg-blue-50/70 dark:bg-blue-900/30'
                    );

                    historyItem.classList.add(
                        'border-transparent'
                    );

                });


            /*
             * Select current history item.
             */
            item.classList.remove('border-transparent');

            item.classList.add(
                'border-blue-500',
                'bg-blue-50/70 dark:bg-blue-900/30'
            );


            const emptyState =
                document.getElementById(
                    'transactionDetailEmpty'
                );

            const content =
                document.getElementById(
                    'transactionDetailContent'
                );

            const status =
                document.getElementById(
                    'detailStatus'
                );

            const statusBadge =
                document.getElementById(
                    'detailStatusBadge'
                );

            const created =
                document.getElementById(
                    'detailCreated'
                );

            const office =
                document.getElementById(
                    'detailOffice'
                );

            const officeInitial =
                document.getElementById(
                    'detailOfficeInitial'
                );

            const destination =
                document.getElementById(
                    'detailDestination'
                );

            const destinationInitial =
                document.getElementById(
                    'detailDestinationInitial'
                );

            const remarks =
                document.getElementById(
                    'detailRemarks'
                );

            const receivedBox =
                document.getElementById(
                    'detailReceivedBox'
                );

            const pendingBox =
                document.getElementById(
                    'detailPendingBox'
                );

            const dateReceived =
                document.getElementById(
                    'detailDateReceived'
                );

            const receivedBy =
                document.getElementById(
                    'detailReceivedBy'
                );

            const receiverInitial =
                document.getElementById(
                    'detailReceiverInitial'
                );


            if (
                !emptyState ||
                !content ||
                !status ||
                !statusBadge
            ) {
                return;
            }


            status.textContent =
                data.status || 'N/A';

            created.textContent =
                data.created || 'No Date Logged';

            office.textContent =
                data.office || 'N/A';

            officeInitial.textContent =
                firstLetter(data.office);

            destination.textContent =
                data.destination || 'N/A';

            destinationInitial.textContent =
                firstLetter(data.destination);

            remarks.textContent =
                data.remarks && data.remarks.trim()
                    ? data.remarks
                    : 'No remarks were provided for this transaction.';


            const isReceived =
                data.received === '1';


            /*
             * Status badge
             */
            statusBadge.className =
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold';


            if (isReceived) {

                statusBadge.textContent = 'Received';

                statusBadge.classList.add(
                    'bg-emerald-50 dark:bg-emerald-900/30',
                    'text-emerald-700 dark:text-emerald-300'
                );

            } else {

                statusBadge.textContent = 'Pending';

                statusBadge.classList.add(
                    'bg-amber-50 dark:bg-amber-900/30',
                    'text-amber-700 dark:text-amber-300'
                );

            }


            /*
             * Received / Pending
             */
            if (isReceived) {

                receivedBox?.classList.remove(
                    'hidden'
                );

                pendingBox?.classList.add(
                    'hidden'
                );

                dateReceived.textContent =
                    data.dateReceived || 'N/A';

                receivedBy.textContent =
                    data.receivedBy || 'N/A';

                receiverInitial.textContent =
                    firstLetter(data.receivedBy);

            } else {

                receivedBox?.classList.add(
                    'hidden'
                );

                pendingBox?.classList.remove(
                    'hidden'
                );

            }


            /*
             * Show transaction details.
             */
            emptyState.classList.add('hidden');

            content.classList.remove('hidden');


            /*
             * Mobile scroll.
             */
            if (window.innerWidth < 1024) {

                window.setTimeout(() => {

                    document
                        .getElementById(
                            'transactionDetailPanel'
                        )
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                }, 50);

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Send Modal
        |--------------------------------------------------------------------------
        */

        function openSendModal() {

            const modal =
                document.getElementById('sendModal');

            const backdrop =
                document.getElementById(
                    'sendModalBackdrop'
                );

            const panel =
                document.getElementById(
                    'sendModalPanel'
                );


            if (!modal || !backdrop || !panel) {
                return;
            }


            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.body.classList.add(
                'overflow-hidden'
            );


            requestAnimationFrame(() => {

                backdrop.classList.remove(
                    'opacity-0'
                );

                backdrop.classList.add(
                    'opacity-100'
                );


                panel.classList.remove(
                    'opacity-0',
                    'scale-95',
                    'translate-y-3'
                );

                panel.classList.add(
                    'opacity-100',
                    'scale-100',
                    'translate-y-0'
                );

            });

        }


        function closeSendModal() {

            const modal =
                document.getElementById('sendModal');

            const backdrop =
                document.getElementById(
                    'sendModalBackdrop'
                );

            const panel =
                document.getElementById(
                    'sendModalPanel'
                );


            if (!modal || !backdrop || !panel) {
                return;
            }


            backdrop.classList.remove(
                'opacity-100'
            );

            backdrop.classList.add(
                'opacity-0'
            );


            panel.classList.remove(
                'opacity-100',
                'scale-100',
                'translate-y-0'
            );

            panel.classList.add(
                'opacity-0',
                'scale-95',
                'translate-y-3'
            );


            window.setTimeout(() => {

                modal.classList.add('hidden');
                modal.classList.remove('flex');

                document.body.classList.remove(
                    'overflow-hidden'
                );

            }, 200);

        }



        /*
        |--------------------------------------------------------------------------
        | Click Handling
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {

                /*
                 * History item
                 */
                const historyItem =
                    event.target.closest(
                        '.transaction-history-item'
                    );

                if (historyItem) {

                    selectTransaction(
                        historyItem
                    );

                    return;

                }


                /*
                 * Open Send Modal
                 */
                if (
                    event.target.closest(
                        '#openSendModal'
                    )
                ) {

                    openSendModal();

                    return;

                }


                /*
                 * Close Send Modal
                 */
                if (
                    event.target.closest(
                        '[data-close-send-modal]'
                    )
                ) {

                    closeSendModal();

                    return;

                }


                /*
                 * Backdrop
                 */
                if (
                    event.target.id ===
                    'sendModalBackdrop'
                ) {

                    closeSendModal();

                    return;

                }


            }
        );



        /*
        |--------------------------------------------------------------------------
        | ESC closes modal
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key !== 'Escape') {
                    return;
                }


                const modal =
                    document.getElementById(
                        'sendModal'
                    );


                if (
                    modal &&
                    !modal.classList.contains('hidden')
                ) {

                    closeSendModal();

                }

            }
        );



        /*
        |--------------------------------------------------------------------------
        | Custom modal events
        |--------------------------------------------------------------------------
        */

        window.addEventListener(
            'open-send-modal',
            openSendModal
        );


        window.addEventListener(
            'close-send-modal',
            closeSendModal
        );



        /*
        |--------------------------------------------------------------------------
        | Livewire Events
        |--------------------------------------------------------------------------
        */

        function registerLivewireEvents() {

            if (
                !window.Livewire ||
                window.__transactionLivewireEventsRegistered
            ) {
                return;
            }


            window.__transactionLivewireEventsRegistered =
                true;


            Livewire.on(
                'close-send-modal',
                () => {

                    closeSendModal();

                }
            );


            /*
             * Compatibility with your existing:
             *
             * $this->dispatch('closeModal');
             */
            Livewire.on(
                'closeModal',
                () => {

                    closeSendModal();

                }
            );

        }


        if (window.Livewire) {

            registerLivewireEvents();

        } else {

            document.addEventListener(
                'livewire:init',
                registerLivewireEvents,
                {
                    once: true
                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Auto-hide messages
        |--------------------------------------------------------------------------
        */

        window.setTimeout(() => {

            const message =
                document.getElementById(
                    'urgentMessage'
                );

            if (!message) {
                return;
            }


            message.style.transition =
                'opacity 200ms ease';

            message.style.opacity = '0';


            window.setTimeout(() => {
                message.remove();
            }, 200);

        }, 4000);


        window.setTimeout(() => {

            const message =
                document.getElementById(
                    'successMessage'
                );

            if (!message) {
                return;
            }


            message.style.transition =
                'opacity 200ms ease';

            message.style.opacity = '0';


            window.setTimeout(() => {
                message.remove();
            }, 200);

        }, 4000);

    })();
</script>
