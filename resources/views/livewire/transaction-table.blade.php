<div class="min-h-screen bg-gray-50/70">

    <section class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- URGENT SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}
            @if (session()->has('urgent_message'))

                <div
                    id="urgentMessage"
                    class="mb-5 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="
                                flex size-9 shrink-0 items-center justify-center rounded-lg

                                {{ $record->is_urgent
                                    ? 'bg-red-50 text-red-600'
                                    : 'bg-emerald-50 text-emerald-600'
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
                            <p class="text-sm font-semibold text-gray-800">
                                {{ session('urgent_message') }}
                            </p>

                            <p class="text-xs text-gray-400">
                                Transaction priority has been updated.
                            </p>
                        </div>

                    </div>


                    <button
                        type="button"
                        onclick="document.getElementById('urgentMessage')?.remove()"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
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
                class="
                    mb-7 overflow-hidden rounded-2xl border bg-white shadow-sm

                    {{ $record->is_urgent
                        ? 'border-red-200'
                        : 'border-gray-200'
                    }}
                "
            >

                {{-- Top accent --}}
                @if ($record->is_urgent)

                    <div class="h-1 bg-gradient-to-r from-red-600 via-red-500 to-orange-500"></div>

                @else

                    <div class="h-1 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500"></div>

                @endif


                <div class="p-6 sm:p-7">

                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">


                        {{-- ================================================= --}}
                        {{-- RECORD INFORMATION --}}
                        {{-- ================================================= --}}
                        <div class="min-w-0 flex-1">

                            <div class="mb-5 flex items-start gap-4">

                                {{-- Main icon --}}
                                <div
                                    class="
                                        flex size-12 shrink-0 items-center justify-center rounded-xl

                                        {{ $record->is_urgent
                                            ? 'bg-red-50 text-red-600'
                                            : 'bg-blue-50 text-blue-600'
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

                                    <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                                        Transaction Record
                                    </p>


                                    <div class="flex flex-wrap items-center gap-2.5">

                                        <h1 class="truncate text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                                            {{ $record->reference }}
                                        </h1>


                                        {{-- Urgent Badge --}}
                                        @if ($record->is_urgent)

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-red-700"
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

                                    </div>


                                    <p class="mt-1 text-sm text-gray-500">
                                        View routing activity and transaction history
                                    </p>

                                </div>

                            </div>



                            {{-- ================================================= --}}
                            {{-- INFORMATION CARDS --}}
                            {{-- ================================================= --}}
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                                {{-- Origin --}}
                                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">

                                    <div class="mb-2 flex items-center gap-2 text-gray-400">

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

                                        <p class="text-[10px] font-bold uppercase tracking-wider">
                                            Origin Office
                                        </p>

                                    </div>


                                    <p class="truncate text-sm font-semibold text-gray-800">
                                        {{ $record->origin }}
                                    </p>

                                </div>


                                {{-- Reference --}}
                                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">

                                    <div class="mb-2 flex items-center gap-2 text-blue-400">

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

                                        <p class="text-[10px] font-bold uppercase tracking-wider">
                                            Reference
                                        </p>

                                    </div>


                                    <p class="truncate text-sm font-bold text-blue-700">
                                        {{ $record->reference }}
                                    </p>

                                </div>


                                {{-- Subject --}}
                                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2 lg:col-span-1">

                                    <div class="mb-2 flex items-center gap-2 text-gray-400">

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

                                        <p class="text-[10px] font-bold uppercase tracking-wider">
                                            Subject
                                        </p>

                                    </div>


                                    <p class="line-clamp-2 text-sm font-medium leading-5 text-gray-800">
                                        {{ $record->subject }}
                                    </p>

                                </div>

                            </div>



                            {{-- ================================================= --}}
                            {{-- URGENT WARNING --}}
                            {{-- ================================================= --}}
                            @if ($record->is_urgent)

                                <div class="mt-4 flex items-start gap-3 rounded-xl border border-red-100 bg-red-50/70 px-4 py-3">

                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">

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

                                        <p class="mt-0.5 text-xs leading-5 text-red-600">
                                            This record has been marked as urgent and requires priority processing.
                                        </p>

                                    </div>

                                </div>

                            @endif

                        </div>



                        {{-- ================================================= --}}
                        {{-- ACTION BUTTONS --}}
                        {{-- ================================================= --}}
                        <div class="flex shrink-0 flex-wrap items-center gap-2 lg:min-w-[180px] lg:flex-col lg:items-stretch">


                            {{-- Send --}}
                            @if ($showSendButton)

                                <button
                                    type="button"
                                    id="openSendModal"
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



                            {{-- ================================================= --}}
                            {{-- URGENT BUTTON --}}
                            {{-- ================================================= --}}
                            <form
                                action="{{ route('records.toggle-urgent', $record->id) }}"
                                method="POST"
                                class="w-full"
                            >
                                @csrf
                                @method('PATCH')


                                @if ($record->is_urgent)

                                    <button
                                        type="submit"
                                        onclick="return confirm('Remove urgent status from this record?')"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition-all duration-150 hover:border-red-300 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
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
                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                            />
                                        </svg>

                                        Remove Urgent

                                    </button>

                                @else

                                    <button
                                        type="submit"
                                        onclick="return confirm('Mark this record as urgent?')"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-red-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
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
                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                            />
                                        </svg>

                                        Mark Urgent

                                    </button>

                                @endif

                            </form>



                            {{-- Print --}}
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
            {{-- HISTORY + DETAILS --}}
            {{-- ========================================================= --}}
            <div class="grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">


                {{-- ===================================================== --}}
                {{-- LEFT SIDE: HISTORY --}}
                {{-- ===================================================== --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 bg-gray-50/70 px-5 py-4">

                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-base font-bold text-gray-900">
                                    Transaction History
                                </h2>

                                <p class="mt-0.5 text-xs text-gray-400">
                                    Select an activity to view details
                                </p>

                            </div>


                            <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-500">
                                {{ $transactions->count() }}
                            </span>

                        </div>

                    </div>



                    <div
                        id="transactionHistoryList"
                        class="max-h-[650px] divide-y divide-gray-100 overflow-y-auto"
                    >

                        @forelse ($transactions as $transact)

                            @php
                                $isReceived = filled($transact->date_recieved);
                            @endphp


                            <button
                                type="button"
                                class="transaction-history-item group flex w-full items-start gap-3 border-l-4 border-transparent px-4 py-4 text-left transition-colors hover:bg-gray-50"

                                data-id="{{ $transact->id }}"

                                data-status="{{ $transact->status }}"

                                data-office="{{ $transact->office }}"

                                data-destination="{{ $transact->destination }}"

                                data-remarks="{{ $transact->remarks }}"

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
                                        flex size-10 shrink-0 items-center justify-center rounded-full

                                        {{ $isReceived
                                            ? 'bg-emerald-50 text-emerald-600'
                                            : 'bg-amber-50 text-amber-600'
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

                                            {{ $transact->status }}

                                        </span>

                                    </div>



                                    <div class="flex min-w-0 items-center gap-1.5">

                                        <span class="max-w-[100px] truncate text-sm font-medium text-gray-700">
                                            {{ $transact->office ?? 'N/A' }}
                                        </span>


                                        <svg
                                            class="size-3 shrink-0 text-gray-300"
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


                                        <span class="max-w-[110px] truncate text-sm font-semibold text-blue-600">
                                            {{ $transact->destination ?? 'N/A' }}
                                        </span>

                                    </div>


                                    <p class="mt-1.5 text-[11px] text-gray-400">
                                        {{ $transact->created_at
                                            ? $transact->created_at->format('M d, Y • h:i A')
                                            : 'No Date Logged'
                                        }}
                                    </p>

                                </div>



                                {{-- Arrow --}}
                                <div class="mt-2 flex size-7 shrink-0 items-center justify-center rounded-lg text-gray-300 transition group-hover:bg-white group-hover:text-blue-500">

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

                                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">

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


                                <p class="text-sm font-semibold text-gray-600">
                                    No transaction history
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
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
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >

                    {{-- Default / empty state --}}
                    <div
                        id="transactionDetailEmpty"
                        class="flex min-h-[500px] flex-col items-center justify-center px-6 py-16 text-center"
                    >

                        <div class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">

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


                        <h3 class="text-lg font-bold text-gray-800">
                            Select a transaction
                        </h3>


                        <p class="mt-2 max-w-md text-sm leading-6 text-gray-400">
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
                        <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-5">

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                <div>

                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                        Transaction Details
                                    </p>


                                    <div class="flex flex-wrap items-center gap-2">

                                        <h3
                                            id="detailStatus"
                                            class="text-xl font-bold text-gray-900"
                                        ></h3>


                                        <span
                                            id="detailStatusBadge"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                        ></span>

                                    </div>

                                </div>


                                <div class="flex items-center gap-1.5 text-xs text-gray-400">

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



                        <div class="space-y-6 p-6">


                            {{-- ================================================= --}}
                            {{-- ROUTING --}}
                            {{-- ================================================= --}}
                            <div>

                                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">
                                    Routing
                                </p>


                                <div class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">


                                    {{-- Sender --}}
                                    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">

                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                            From
                                        </p>


                                        <div class="flex items-center gap-3">

                                            <div
                                                id="detailOfficeInitial"
                                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-bold uppercase text-gray-700"
                                            ></div>


                                            <div class="min-w-0">

                                                <p
                                                    id="detailOffice"
                                                    class="truncate text-sm font-semibold text-gray-800"
                                                ></p>

                                                <p class="text-[11px] text-gray-400">
                                                    Sending office
                                                </p>

                                            </div>

                                        </div>

                                    </div>



                                    {{-- Arrow --}}
                                    <div class="hidden sm:flex">

                                        <div class="flex size-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 shadow-sm">

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
                                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">

                                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-blue-400">
                                            Destination
                                        </p>


                                        <div class="flex items-center gap-3">

                                            <div
                                                id="detailDestinationInitial"
                                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold uppercase text-blue-700"
                                            ></div>


                                            <div class="min-w-0">

                                                <p
                                                    id="detailDestination"
                                                    class="truncate text-sm font-semibold text-blue-800"
                                                ></p>

                                                <p class="text-[11px] text-blue-500">
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
                            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-5">

                                <div class="mb-3 flex items-center gap-2">

                                    <div class="flex size-8 items-center justify-center rounded-lg bg-white text-gray-500 ring-1 ring-gray-200">

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


                                    <span class="text-xs font-bold uppercase tracking-wide text-gray-400">
                                        Remarks
                                    </span>

                                </div>


                                <p
                                    id="detailRemarks"
                                    class="text-sm leading-6 text-gray-700"
                                ></p>

                            </div>



                            {{-- ================================================= --}}
                            {{-- RECEIVED --}}
                            {{-- ================================================= --}}
                            <div
                                id="detailReceivedBox"
                                class="hidden rounded-xl border border-emerald-100 bg-emerald-50/70 p-5"
                            >

                                <div class="mb-5 flex items-center gap-3">

                                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

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

                                        <p class="mt-0.5 text-xs text-emerald-600">
                                            Successfully acknowledged by the receiving office
                                        </p>

                                    </div>

                                </div>


                                <div class="grid gap-4 sm:grid-cols-2">

                                    <div class="rounded-lg bg-white/70 p-3">

                                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70">
                                            Received Date
                                        </p>

                                        <p
                                            id="detailDateReceived"
                                            class="mt-1 text-sm font-semibold text-emerald-900"
                                        ></p>

                                    </div>


                                    <div class="rounded-lg bg-white/70 p-3">

                                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70">
                                            Received By
                                        </p>


                                        <div class="mt-1.5 flex items-center gap-2">

                                            <div
                                                id="detailReceiverInitial"
                                                class="flex size-7 items-center justify-center rounded-full bg-white text-[10px] font-bold uppercase text-emerald-700 shadow-sm"
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
                                class="hidden rounded-xl border border-amber-100 bg-amber-50/70 p-5"
                            >

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                    <div class="flex items-start gap-3">

                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">

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

                                            <p class="mt-1 max-w-md text-xs leading-5 text-amber-700">
                                                This transaction has not yet been acknowledged by the receiving office.
                                            </p>

                                        </div>

                                    </div>



                                    <button
                                        type="button"
                                        id="detailMarkReceivedButton"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
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
                                                d="m4.5 12.75 6 6 9-13.5"
                                            />
                                        </svg>

                                        Mark as Received

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

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
            class="relative w-full max-w-lg translate-y-3 scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl transition-all duration-200"
        >

            {{-- Header --}}
            <div class="border-b border-gray-100 px-6 py-5">

                <div class="flex items-center justify-between">

                    <div class="flex items-center gap-3">

                        <div class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

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
                        data-close-send-modal
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
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
                    class="mx-6 mt-5 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"
                >

                    <span class="text-sm font-medium">
                        {{ session('message') }}
                    </span>


                    <button
                        type="button"
                        onclick="document.getElementById('successMessage')?.remove()"
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



            {{-- Body --}}
            <div class="space-y-5 px-6 py-5">


                {{-- Destination --}}
                <div>

                    <label
                        for="sendOffice"
                        class="mb-1.5 block text-sm font-semibold text-gray-700"
                    >
                        Destination Office
                        <span class="text-red-500">*</span>
                    </label>


                    <select
                        wire:model="office"
                        id="sendOffice"
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
                        for="sendStatus"
                        class="mb-1.5 block text-sm font-semibold text-gray-700"
                    >
                        Status
                        <span class="text-red-500">*</span>
                    </label>


                    <select
                        wire:model="status"
                        id="sendStatus"
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

                    <div class="mb-1.5 flex items-center justify-between">

                        <label
                            for="sendRemarks"
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
                        id="sendRemarks"
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



            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/60 px-6 py-4">

                <button
                    type="button"
                    data-close-send-modal
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
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
                        'bg-blue-50/70'
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
                'bg-blue-50/70'
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
                    'bg-emerald-50',
                    'text-emerald-700'
                );

            } else {

                statusBadge.textContent = 'Pending';

                statusBadge.classList.add(
                    'bg-amber-50',
                    'text-amber-700'
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


                /*
                 * Mark as Received
                 */
                const receivedButton =
                    event.target.closest(
                        '#detailMarkReceivedButton'
                    );

                if (receivedButton) {

                    if (!selectedTransactionId) {
                        return;
                    }


                    const component =
                        getCurrentLivewireComponent(
                            receivedButton
                        );


                    if (!component) {
                        console.error(
                            'Livewire component not found.'
                        );

                        return;
                    }


                    receivedButton.disabled = true;


                    component.call(
                        'markAsReceived',
                        Number(selectedTransactionId)
                    );

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