<div class="min-h-screen bg-gray-50/70">

    <section class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- ========================================================= --}}
            {{-- SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}
            @if (session()->has('message'))
                <div
                    class="mb-5 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600"
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

                            <p class="text-xs text-emerald-600">
                                Transaction updated successfully.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        onclick="this.parentElement.remove()"
                        class="rounded-lg p-1.5 text-emerald-500 transition hover:bg-emerald-100 hover:text-emerald-700"
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
            <div
                class="mb-7 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >

                {{-- Top Accent --}}
                <div
                    class="h-1 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500"
                ></div>


                <div class="p-6 sm:p-7">

                    <div
                        class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between"
                    >

                        {{-- ================================================= --}}
                        {{-- RECORD INFORMATION --}}
                        {{-- ================================================= --}}
                        <div class="min-w-0 flex-1">

                            <div class="mb-5 flex items-start gap-4">

                                {{-- Main Icon --}}
                                <div
                                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
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
                                        class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-400"
                                    >
                                        Transaction Record
                                    </p>

                                    <h1
                                        class="truncate text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl"
                                    >
                                        {{ $record->reference ?? 'N/A' }}
                                    </h1>

                                    <p class="mt-1 text-sm text-gray-500">
                                        View transaction details and routing history
                                    </p>

                                </div>

                            </div>


                            {{-- ================================================= --}}
                            {{-- INFORMATION CARDS --}}
                            {{-- ================================================= --}}
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                                {{-- Origin --}}
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-4"
                                >

                                    <div
                                        class="mb-2 flex items-center gap-2 text-gray-400"
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
                                                d="M12 21a9 9 0 100-18 9 9 0 000 18z"
                                            />
                                        </svg>

                                        <p
                                            class="text-[10px] font-bold uppercase tracking-wider"
                                        >
                                            Origin Office
                                        </p>
                                    </div>

                                    <p
                                        class="truncate text-sm font-semibold text-gray-800"
                                    >
                                        {{ $record->origin ?? 'N/A' }}
                                    </p>

                                </div>


                                {{-- Reference --}}
                                <div
                                    class="rounded-xl border border-blue-100 bg-blue-50/50 p-4"
                                >

                                    <div
                                        class="mb-2 flex items-center gap-2 text-blue-400"
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
                                                d="M4.5 6.75h15m-15 5.25h15m-15 5.25h9"
                                            />
                                        </svg>

                                        <p
                                            class="text-[10px] font-bold uppercase tracking-wider"
                                        >
                                            Reference
                                        </p>
                                    </div>

                                    <p
                                        class="truncate text-sm font-bold text-blue-700"
                                    >
                                        {{ $record->reference ?? 'N/A' }}
                                    </p>

                                </div>


                                {{-- Subject --}}
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2 lg:col-span-1"
                                >

                                    <div
                                        class="mb-2 flex items-center gap-2 text-gray-400"
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
                                                d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25H12"
                                            />
                                        </svg>

                                        <p
                                            class="text-[10px] font-bold uppercase tracking-wider"
                                        >
                                            Subject
                                        </p>
                                    </div>

                                    <p
                                        class="line-clamp-2 text-sm font-medium leading-5 text-gray-800"
                                    >
                                        {{ $record->subject ?? 'N/A' }}
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- ACTION BUTTONS --}}
                        {{-- ================================================= --}}
                        <div
                            class="flex shrink-0 flex-wrap items-center gap-2 lg:min-w-[170px] lg:flex-col lg:items-stretch"
                        >

                            @if ($showSendButton)
                                <button
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sendModal"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
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
                                            d="M6 12 3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5"
                                        />
                                    </svg>

                                    Send
                                </button>
                            @endif


                            <a
                                href="{{ route('records-pdf', $record->id) }}"
                                target="_blank"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900"
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
                                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18"
                                    />
                                </svg>

                                Print
                            </a>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- TRANSACTION HISTORY --}}
            {{-- ========================================================= --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >

                {{-- Section Header --}}
                <div
                    class="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6"
                >
                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <h2 class="text-base font-bold text-gray-900">
                                Transaction History
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-400">
                                Routing and receiving activity for this record
                            </p>
                        </div>


                        <span
                            class="shrink-0 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-500"
                        >
                            {{ $transactions->count() }}
                        </span>

                    </div>
                </div>


                {{-- Timeline --}}
                <div class="p-5 sm:p-6 lg:p-8">

                    @forelse ($transactions as $transact)

                        @php
                            $isReceived = filled($transact->date_recieved);
                        @endphp


                        <div
                            class="group relative pb-10 pl-12 last:pb-0 sm:pl-14"
                        >

                            {{-- Vertical Line --}}
                            @if (!$loop->last)
                                <div
                                    class="absolute left-[19px] top-10 h-[calc(100%-1.25rem)] w-px bg-gray-200 sm:left-[23px]"
                                ></div>
                            @endif


                            {{-- Timeline Icon --}}
                            <div
                                class="
                                    absolute left-0 top-0 z-10
                                    flex size-10 items-center justify-center rounded-full
                                    border-4 border-white shadow-sm
                                    sm:size-12

                                    {{ $isReceived
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'
                                    }}
                                "
                            >
                                @if ($isReceived)

                                    <svg
                                        class="size-4 sm:size-5"
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
                                        class="size-4 sm:size-5"
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
                                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:border-gray-300 hover:shadow-md"
                            >

                                {{-- Transaction Header --}}
                                <div
                                    class="border-b border-gray-100 bg-gray-50/60 px-5 py-4 sm:px-6"
                                >

                                    <div
                                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
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
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : 'bg-amber-50 text-amber-700'
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
                                                            ? 'text-emerald-600'
                                                            : 'text-amber-600'
                                                        }}
                                                    "
                                                >
                                                    {{ $isReceived ? 'Received' : 'Awaiting Receipt' }}
                                                </span>

                                            </div>


                                            <h3
                                                class="text-base font-bold text-gray-900 sm:text-lg"
                                            >
                                                {{ $transact->status ?? 'Transaction Activity' }}
                                            </h3>

                                        </div>


                                        {{-- Logged Date --}}
                                        <div
                                            class="flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500"
                                        >
                                            <svg
                                                class="size-4 text-gray-400"
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
                                                    ? $transact->created_at->format('M d, Y • h:i A')
                                                    : 'No Date Logged'
                                                }}
                                            </span>
                                        </div>

                                    </div>

                                </div>


                                <div class="space-y-5 p-5 sm:p-6">

                                    {{-- ================================================= --}}
                                    {{-- ROUTING --}}
                                    {{-- ================================================= --}}
                                    <div>

                                        <p
                                            class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400"
                                        >
                                            Routing
                                        </p>


                                        <div
                                            class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center"
                                        >

                                            {{-- Sender --}}
                                            <div
                                                class="rounded-xl border border-gray-200 bg-gray-50/60 p-4"
                                            >
                                                <p
                                                    class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400"
                                                >
                                                    From
                                                </p>


                                                <div class="flex items-center gap-3">

                                                    <div
                                                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-bold uppercase text-gray-700"
                                                    >
                                                        {{ strtoupper(substr($transact->office ?? '?', 0, 1)) }}
                                                    </div>


                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-sm font-semibold text-gray-800"
                                                        >
                                                            {{ $transact->office ?? 'N/A' }}
                                                        </p>

                                                        <p
                                                            class="text-[11px] text-gray-400"
                                                        >
                                                            Sending office
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>


                                            {{-- Arrow --}}
                                            <div class="hidden sm:flex">

                                                <div
                                                    class="flex size-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 shadow-sm"
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
                                                class="rounded-xl border border-blue-100 bg-blue-50/70 p-4"
                                            >
                                                <p
                                                    class="mb-2 text-[10px] font-bold uppercase tracking-wider text-blue-400"
                                                >
                                                    Destination
                                                </p>


                                                <div class="flex items-center gap-3">

                                                    <div
                                                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold uppercase text-blue-700"
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
                                                            class="text-[11px] text-blue-500"
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
                                        class="rounded-xl border border-gray-200 bg-gray-50/50 p-5"
                                    >

                                        <div class="mb-3 flex items-center gap-2">

                                            <div
                                                class="flex size-8 items-center justify-center rounded-lg bg-white text-gray-500 ring-1 ring-gray-200"
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
                                                class="text-xs font-bold uppercase tracking-wide text-gray-400"
                                            >
                                                Remarks
                                            </span>

                                        </div>


                                        <p
                                            class="whitespace-pre-line text-sm leading-6 text-gray-700"
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
                                            class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5"
                                        >

                                            <div
                                                class="mb-5 flex items-center gap-3"
                                            >

                                                <div
                                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
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
                                                        class="mt-0.5 text-xs text-emerald-600"
                                                    >
                                                        Successfully acknowledged by the receiving office
                                                    </p>
                                                </div>

                                            </div>


                                            <div
                                                class="grid gap-4 sm:grid-cols-2"
                                            >

                                                {{-- Received Date --}}
                                                <div
                                                    class="rounded-lg bg-white/70 p-3"
                                                >
                                                    <p
                                                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70"
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
                                                    class="rounded-lg bg-white/70 p-3"
                                                >
                                                    <p
                                                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70"
                                                    >
                                                        Received By
                                                    </p>


                                                    <div
                                                        class="mt-1.5 flex items-center gap-2"
                                                    >

                                                        <div
                                                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white text-[10px] font-bold uppercase text-emerald-700 shadow-sm"
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
                                            class="rounded-xl border border-amber-100 bg-amber-50/70 p-5"
                                        >

                                            <div
                                                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                            >

                                                <div
                                                    class="flex items-start gap-3"
                                                >

                                                    <div
                                                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"
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
                                                            class="mt-1 max-w-md text-xs leading-5 text-amber-700"
                                                        >
                                                            This transaction has not yet been acknowledged by the receiving office.
                                                        </p>
                                                    </div>

                                                </div>


                                                <button
                                                    type="button"
                                                    wire:click="markAsReceived({{ $transact->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="markAsReceived({{ $transact->id }})"
                                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                >

                                                    <svg
                                                        wire:loading.remove
                                                        wire:target="markAsReceived({{ $transact->id }})"
                                                        class="size-4"
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
                                                        wire:target="markAsReceived({{ $transact->id }})"
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


                                                    <span
                                                        wire:loading.remove
                                                        wire:target="markAsReceived({{ $transact->id }})"
                                                    >
                                                        Mark as Received
                                                    </span>

                                                    <span
                                                        wire:loading
                                                        wire:target="markAsReceived({{ $transact->id }})"
                                                    >
                                                        Updating...
                                                    </span>

                                                </button>

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
                                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400"
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

                            <h3 class="text-base font-bold text-gray-700">
                                No transaction history
                            </h3>

                            <p class="mt-1 text-sm text-gray-400">
                                Routing activities will appear here once available.
                            </p>

                        </div>

                    @endforelse

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
                class="modal-content overflow-hidden rounded-2xl border-0 bg-white shadow-2xl"
            >

                {{-- ================================================= --}}
                {{-- MODAL HEADER --}}
                {{-- ================================================= --}}
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-6 py-5"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
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
                                class="text-lg font-bold text-gray-900"
                            >
                                Send Transaction
                            </h2>

                            <p class="text-xs text-gray-400">
                                Forward this record to another office
                            </p>
                        </div>

                    </div>


                    <button
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        class="flex size-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
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
                        class="mx-6 mt-5 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"
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
                            class="ml-3 text-emerald-600 hover:text-emerald-800"
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
                            class="mb-1.5 block text-sm font-semibold text-gray-700"
                        >
                            Destination Office
                            <span class="text-red-500">*</span>
                        </label>


                        <select
                            wire:model="office"
                            id="office"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
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
                            <p class="mt-1.5 text-xs font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Status --}}
                    <div>

                        <label
                            for="status"
                            class="mb-1.5 block text-sm font-semibold text-gray-700"
                        >
                            Status
                            <span class="text-red-500">*</span>
                        </label>


                        <select
                            wire:model="status"
                            id="status"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
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
                            <p class="mt-1.5 text-xs font-medium text-red-600">
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
                                class="text-sm font-semibold text-gray-700"
                            >
                                Remarks
                            </label>

                            <span class="text-[11px] text-gray-400">
                                Optional
                            </span>
                        </div>


                        <textarea
                            wire:model="remarks"
                            id="remarks"
                            rows="4"
                            class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Add a short note or instruction..."
                        ></textarea>


                        @error('remarks')
                            <p class="mt-1.5 text-xs font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- MODAL FOOTER --}}
                {{-- ================================================= --}}
                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/60 px-6 py-4"
                >

                    <button
                        id="modalCloseBtn"
                        type="button"
                        data-bs-dismiss="modal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
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