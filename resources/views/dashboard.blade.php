<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}
    <x-slot name="header">

        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ __('Dashboard') }}
                </h2>

                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ Auth::user()->office }}
                </p>
            </div>


            <div class="hidden items-center gap-2 text-sm text-gray-400 dark:text-gray-500 sm:flex">

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
                        d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5A1.5 1.5 0 0020.25 19.5V6.75a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5V19.5A1.5 1.5 0 005.25 21z"
                    />
                </svg>

                {{ now()->format('l, F j, Y') }}

            </div>

        </div>

    </x-slot>



    <div class="min-h-screen bg-gray-50/70 dark:bg-gray-900">

        <div class="mx-auto max-w-7xl space-y-7 px-4 py-7 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- WELCOME / OFFICE SUMMARY --}}
            {{-- ========================================================= --}}
            <section
                class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm"
            >

                {{-- Accent --}}
                <div class="absolute inset-y-0 left-0 w-1 bg-blue-600"></div>


                <div
                    class="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between lg:px-7"
                >

                    <div>

                        <p
                            class="text-xs font-bold uppercase tracking-[0.18em] text-blue-500 dark:text-blue-400"
                        >
                            Records Management System
                        </p>


                        <h1
                            class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100"
                        >
                            Welcome, {{ Auth::user()->name }}
                        </h1>


                        <p
                            class="mt-1 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400"
                        >
                            Monitor incoming documents, outgoing records,
                            urgent transactions, and recent office activity.
                        </p>

                    </div>



                    {{-- Office --}}
                    <div
                        class="flex shrink-0 items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-900 px-4 py-3"
                    >

                        <div
                            class="flex size-10 items-center justify-center rounded-full bg-white dark:!bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm"
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
                                    d="M3 21h18M5.25 21V7.5L12 3l6.75 4.5V21M9 9.75h.008v.008H9V9.75zm0 3h.008v.008H9v-.008zm0 3h.008v.008H9v-.008zm6-6h.008v.008H15V9.75zm0 3h.008v.008H15v-.008zm0 3h.008v.008H15v-.008z"
                                />
                            </svg>

                        </div>


                        <div>

                            <p
                                class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                            >
                                Current Office
                            </p>

                            <p
                                class="max-w-[220px] truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                            >
                                {{ Auth::user()->office }}
                            </p>

                        </div>

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- MAIN STATISTICS --}}
            {{-- ========================================================= --}}
            <section>

                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >


                    {{-- ================================================= --}}
                    {{-- INCOMING --}}
                    {{-- ================================================= --}}
                    <a
                        href="{{ route('incoming-record') }}"
                        class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                    >

                        <div class="mb-4 flex items-start justify-between">

                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition-colors group-hover:bg-blue-100"
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
                                        d="M12 3v12m0 0 4.5-4.5M12 15l-4.5-4.5M5.25 21h13.5"
                                    />
                                </svg>

                            </div>


                            <svg
                                class="size-4 text-gray-300 dark:text-gray-600 transition-all group-hover:translate-x-0.5 group-hover:text-blue-500"
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


                        <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ number_format($incomingRecords) }}
                        </p>


                        <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Incoming Records
                        </p>


                        <p class="mt-1 text-xs leading-5 text-gray-400 dark:text-gray-500">
                            Unique records routed to your office
                        </p>

                    </a>



                    {{-- ================================================= --}}
                    {{-- OUTGOING --}}
                    {{-- ================================================= --}}
                    <a
                        href="{{ route('outgoing-record') }}"
                        class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md"
                    >

                        <div class="mb-4 flex items-start justify-between">

                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 transition-colors group-hover:bg-emerald-100"
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
                                        d="M12 21V9m0 0 4.5 4.5M12 9l-4.5 4.5M5.25 3h13.5"
                                    />
                                </svg>

                            </div>


                            <svg
                                class="size-4 text-gray-300 dark:text-gray-600 transition-all group-hover:translate-x-0.5 group-hover:text-emerald-500"
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


                        <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ number_format($outgoingRecords) }}
                        </p>


                        <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Outgoing Records
                        </p>


                        <p class="mt-1 text-xs leading-5 text-gray-400 dark:text-gray-500">
                            Records originating from your office
                        </p>

                    </a>



                    {{-- ================================================= --}}
                    {{-- AWAITING RECEIPT --}}
                    {{-- ================================================= --}}
                    <a
                        href="{{ route('incoming-record') }}"
                        class="
                            group rounded-2xl border bg-white dark:!bg-gray-800 p-5 shadow-sm
                            transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md

                            {{ $awaitingReceipt > 0
                                ? 'border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700'
                                : 'border-gray-200 dark:border-gray-700'
                            }}
                        "
                    >

                        <div class="mb-4 flex items-start justify-between">

                            <div
                                class="flex size-11 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400"
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


                            @if ($awaitingReceipt > 0)

                                <span class="relative flex size-2.5">

                                    <span
                                        class="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-40"
                                    ></span>

                                    <span
                                        class="relative inline-flex size-2.5 rounded-full bg-amber-500"
                                    ></span>

                                </span>

                            @endif

                        </div>


                        <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ number_format($awaitingReceipt) }}
                        </p>


                        <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Awaiting Receipt
                        </p>


                        <p class="mt-1 text-xs leading-5 text-gray-400 dark:text-gray-500">
                            Transactions waiting for acknowledgement
                        </p>

                    </a>



                    {{-- ================================================= --}}
                    {{-- URGENT --}}
                    {{-- ================================================= --}}
                    <div
                        class="
                            rounded-2xl border bg-white dark:!bg-gray-800 p-5 shadow-sm

                            {{ $urgentCount > 0
                                ? 'border-red-200 dark:border-red-800'
                                : 'border-gray-200 dark:border-gray-700'
                            }}
                        "
                    >

                        <div class="mb-4 flex items-start justify-between">

                            <div
                                class="
                                    flex size-11 items-center justify-center rounded-xl

                                    {{ $urgentCount > 0
                                        ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                        : 'bg-gray-50 dark:bg-gray-900 text-gray-400 dark:text-gray-500'
                                    }}
                                "
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
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                    />
                                </svg>

                            </div>


                            @if ($urgentCount > 0)

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-red-700 dark:text-red-300"
                                >

                                    <span
                                        class="size-1.5 rounded-full bg-red-500"
                                    ></span>

                                    Attention

                                </span>

                            @endif

                        </div>


                        <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ number_format($urgentCount) }}
                        </p>


                        <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Urgent Records
                        </p>


                        <p class="mt-1 text-xs leading-5 text-gray-400 dark:text-gray-500">
                            Priority records involving your office
                        </p>

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- TODAY'S INFORMATION --}}
            {{-- ========================================================= --}}
            <section>

                <div class="mb-3">

                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Today's Overview
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        Activity recorded today
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">


                    {{-- Activity Today --}}
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-5 shadow-sm"
                    >

                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400"
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
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5A1.5 1.5 0 0020.25 19.5V6.75a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5V19.5A1.5 1.5 0 005.25 21z"
                                />
                            </svg>

                        </div>


                        <div class="min-w-0">

                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($activityToday) }}
                            </p>

                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Activities Today
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                Transactions sent or received by your office
                            </p>

                        </div>

                    </div>



                    {{-- Received Today --}}
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-5 shadow-sm"
                    >

                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400"
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


                        <div class="min-w-0">

                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($receivedToday) }}
                            </p>

                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Received Today
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                Transactions acknowledged by your office
                            </p>

                        </div>

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- QUICK ACCESS --}}
            {{-- ========================================================= --}}
            <section>

                <div class="mb-3">

                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Quick Access
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        Manage your document workflows
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">


                    {{-- Incoming --}}
                    <a
                        href="{{ route('incoming-record') }}"
                        class="group overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                    >

                        <div class="flex items-center gap-4">

                            <div
                                class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-900/30 transition-colors group-hover:bg-blue-100"
                            >

                                <img
                                    src="{{ asset('img/docu-in.png') }}"
                                    alt="Incoming Record"
                                    class="size-9 object-contain transition-transform duration-200 group-hover:scale-110"
                                >

                            </div>


                            <div class="min-w-0 flex-1">

                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    Incoming Records
                                </h3>


                                <p class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400">
                                    Receive, search, acknowledge and forward
                                    incoming transactions.
                                </p>


                                <div
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400"
                                >

                                    Open Incoming


                                    <svg
                                        class="size-3.5 transition-transform group-hover:translate-x-1"
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

                            </div>

                        </div>

                    </a>



                    {{-- Outgoing --}}
                    <a
                        href="{{ route('outgoing-record') }}"
                        class="group overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md"
                    >

                        <div class="flex items-center gap-4">

                            <div
                                class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 transition-colors group-hover:bg-emerald-100"
                            >

                                <img
                                    src="{{ asset('img/docu-out.png') }}"
                                    alt="Outgoing Record"
                                    class="size-9 object-contain transition-transform duration-200 group-hover:scale-110"
                                >

                            </div>


                            <div class="min-w-0 flex-1">

                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    Outgoing Records
                                </h3>


                                <p class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400">
                                    Create, send and monitor records
                                    originating from your office.
                                </p>


                                <div
                                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                                >

                                    Open Outgoing


                                    <svg
                                        class="size-3.5 transition-transform group-hover:translate-x-1"
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

                            </div>

                        </div>

                    </a>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- RECENT ACTIVITY + URGENT RECORDS --}}
            {{-- ========================================================= --}}
            <section
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]"
            >


                {{-- ===================================================== --}}
                {{-- RECENT ACTIVITY --}}
                {{-- ===================================================== --}}
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm"
                >

                    {{-- Header --}}
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900 px-5 py-4"
                    >

                        <div>

                            <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                Recent Activity
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                Latest transactions involving your office
                            </p>

                        </div>


                        <a
                            href="{{ route('incoming-record') }}"
                            class="text-xs font-semibold text-blue-600 dark:text-blue-400 transition hover:text-blue-700 dark:hover:text-blue-300"
                        >
                            View records
                        </a>

                    </div>



                    {{-- Activity --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">

                        @forelse ($recentTransactions as $transaction)

                            @php
                                $received = filled($transaction->date_recieved);
                                $record = $transaction->record;
                            @endphp


                            @if ($record)

                                <a
                                    href="{{ route('show-transactions', $record->id) }}"
                                    class="group flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                >

                                    {{-- Icon --}}
                                    <div
                                        class="
                                            mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full

                                            {{ $received
                                                ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
                                                : 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                                            }}
                                        "
                                    >

                                        @if ($received)

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



                                    <div class="min-w-0 flex-1">

                                        {{-- Reference + Time --}}
                                        <div
                                            class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                                        >

                                            <div class="flex min-w-0 items-center gap-2">

                                                <p
                                                    class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100 transition-colors group-hover:text-blue-600"
                                                >
                                                    {{ $record->reference }}
                                                </p>


                                                @if ($record->is_urgent)

                                                    <span
                                                        class="shrink-0 rounded-full bg-red-50 dark:bg-red-900/30 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-red-600 dark:text-red-400"
                                                    >
                                                        Urgent
                                                    </span>

                                                @endif

                                            </div>


                                            <span
                                                class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500"
                                            >
                                                {{ $transaction->created_at?->diffForHumans() }}
                                            </span>

                                        </div>



                                        {{-- Subject --}}
                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-400 dark:text-gray-500"
                                        >
                                            {{ $record->subject }}
                                        </p>



                                        {{-- Route --}}
                                        <div
                                            class="mt-2 flex min-w-0 items-center gap-1.5"
                                        >

                                            <span
                                                class="max-w-[130px] truncate text-xs font-medium text-gray-600 dark:text-gray-300"
                                            >
                                                {{ $transaction->office ?? 'N/A' }}
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


                                            <span
                                                class="max-w-[130px] truncate text-xs font-semibold text-blue-600 dark:text-blue-400"
                                            >
                                                {{ $transaction->destination ?? 'N/A' }}
                                            </span>


                                            <span
                                                class="
                                                    ml-auto hidden shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold sm:inline-flex

                                                    {{ $received
                                                        ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                        : 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                                    }}
                                                "
                                            >
                                                {{ $transaction->status ?? 'N/A' }}
                                            </span>

                                        </div>

                                    </div>

                                </a>

                            @endif


                        @empty

                            <div class="px-6 py-14 text-center">

                                <div
                                    class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500"
                                >

                                    <svg
                                        class="size-5"
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
                                    No recent activity
                                </p>


                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    New transaction activity will appear here.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- URGENT ATTENTION --}}
                {{-- ===================================================== --}}
                <div
                    class="
                        overflow-hidden rounded-2xl border bg-white dark:!bg-gray-800 shadow-sm

                        {{ $urgentCount > 0
                            ? 'border-red-200 dark:border-red-800'
                            : 'border-gray-200 dark:border-gray-700'
                        }}
                    "
                >

                    {{-- Header --}}
                    <div
                        class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900 px-5 py-4"
                    >

                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    Urgent Attention
                                </h2>

                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    Priority records involving your office
                                </p>

                            </div>


                            @if ($urgentCount > 0)

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-1 text-xs font-bold text-red-700 dark:text-red-300"
                                >

                                    <span
                                        class="size-1.5 rounded-full bg-red-500"
                                    ></span>

                                    {{ $urgentCount }}

                                </span>

                            @endif

                        </div>

                    </div>



                    {{-- Urgent records --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">

                        @forelse ($urgentRecords as $urgent)

                            <a
                                href="{{ route('show-transactions', $urgent->id) }}"
                                class="group block px-5 py-4 transition-colors hover:bg-red-50/30 dark:hover:bg-red-900/40"
                            >

                                <div class="flex items-start gap-3">

                                    {{-- Warning --}}
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400"
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

                                    </div>



                                    <div class="min-w-0 flex-1">

                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >

                                            <p
                                                class="truncate text-sm font-bold text-gray-800 dark:text-gray-100 transition-colors group-hover:text-red-700"
                                            >
                                                {{ $urgent->reference }}
                                            </p>


                                            <svg
                                                class="size-3.5 shrink-0 text-gray-300 dark:text-gray-600 transition-all group-hover:translate-x-0.5 group-hover:text-red-500"
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



                                        <p
                                            class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500 dark:text-gray-400"
                                        >
                                            {{ $urgent->subject }}
                                        </p>



                                        <div
                                            class="mt-2 flex items-center justify-between gap-2"
                                        >

                                            <span
                                                class="max-w-[160px] truncate text-[11px] text-gray-400 dark:text-gray-500"
                                            >
                                                {{ $urgent->origin }}
                                            </span>


                                            <span
                                                class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500"
                                            >
                                                {{ $urgent->updated_at?->diffForHumans() }}
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </a>


                        @empty

                            <div class="px-6 py-14 text-center">

                                <div
                                    class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400"
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


                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    All clear
                                </p>


                                <p
                                    class="mt-1 text-xs leading-5 text-gray-400 dark:text-gray-500"
                                >
                                    No urgent records currently require attention.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- BOTTOM SUMMARY --}}
            {{-- ========================================================= --}}
            <section
                class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-5 py-4 shadow-sm"
            >

                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400"
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
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                                />
                            </svg>

                        </div>


                        <div>

                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Office Activity Summary
                            </p>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Information is based on records involving
                                {{ Auth::user()->office }}.
                            </p>

                        </div>

                    </div>


                    <div
                        class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500 dark:text-gray-400"
                    >

                        <span>
                            <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                {{ number_format($incomingRecords) }}
                            </strong>
                            incoming
                        </span>

                        <span>
                            <strong class="font-semibold text-gray-700 dark:text-gray-200">
                                {{ number_format($outgoingRecords) }}
                            </strong>
                            outgoing
                        </span>

                        <span>
                            <strong
                                class="
                                    font-semibold

                                    {{ $awaitingReceipt > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-gray-700 dark:text-gray-200'
                                    }}
                                "
                            >
                                {{ number_format($awaitingReceipt) }}
                            </strong>
                            pending
                        </span>

                        <span>
                            <strong
                                class="
                                    font-semibold

                                    {{ $urgentCount > 0
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-gray-700 dark:text-gray-200'
                                    }}
                                "
                            >
                                {{ number_format($urgentCount) }}
                            </strong>
                            urgent
                        </span>

                    </div>

                </div>

            </section>


        </div>

    </div>


    {{-- Existing flash component --}}
    <x-flash-message />

</x-app-layout>