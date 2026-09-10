<div class="w-full min-h-screen bg-gray-50/60 dark:bg-gray-900 flex flex-col pt-6">

    <section class="mt-2">

        {{-- Optional latest outgoing record --}}
        @if($latest ?? false)
            <div class="mx-auto max-w-full px-4 lg:px-8 mb-5">
                <div class="relative flex items-center gap-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 pl-5 pr-5 py-3 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 bg-emerald-600"></span>

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
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                            />
                        </svg>
                    </div>

                    <div class="grid min-w-0 flex-1 grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-3">
                        <div>
                            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                Latest record
                            </p>

                            <p class="truncate text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ $latest->reference }}
                            </p>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                Subject
                            </p>

                            <p class="truncate text-sm text-gray-700 dark:text-gray-200">
                                {{ $latest->subject }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                Created
                            </p>

                            <p class="text-sm text-gray-700 dark:text-gray-200">
                                {{ $latest->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif


        <div class="mx-auto max-w-full px-4 lg:px-8">

            {{-- Toolbar --}}
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-3">

                    {{-- Outgoing button --}}
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-send-modal')"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-emerald-700"
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
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>

                        Outgoing
                    </button>

                    {{-- Total records --}}
                    <span class="hidden text-sm text-gray-400 dark:text-gray-500 sm:inline">
                        {{ $data->total() }}
                        {{ Str::plural('record', $data->total()) }}
                    </span>

                </div>


                {{-- Search --}}
                <div class="relative w-full sm:w-80">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"
                        />
                    </svg>

                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 py-2 pl-9 pr-9 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        placeholder="Search records..."
                    >

                    <button
                        type="button"
                        x-data
                        x-show="$wire.search && $wire.search.length > 0"
                        x-cloak
                        @click="$wire.set('search', '')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                        aria-label="Clear search"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                </div>
            </div>


            {{-- Table card --}}
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm">

                <div class="h-[600px] overflow-y-auto">

                    <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">

                        <thead class="sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700 bg-gray-50/95 dark:bg-gray-900 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 backdrop-blur">
                            <tr>
                                <th class="px-4 py-3">
                                    References
                                </th>

                                <th class="px-4 py-3">
                                    Subject
                                </th>

                                <th class="px-4 py-3">
                                    Author
                                </th>

                                <th class="px-4 py-3">
                                    Date Created
                                </th>

                                <th class="px-4 py-3 text-right">
                                    RAS Audit Trail
                                </th>
                            </tr>
                        </thead>


                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                            @forelse ($data as $record)

                                @php
                                    $date = $record->created_at;

                                    /*
                                     * Optional stale indicator.
                                     * Remove this if you don't need it.
                                     */
                                    $isStale = $date && $date->diffInDays(now()) >= 5;
                                @endphp

                                <tr
                                    wire:key="outgoing-record-{{ $record->id }}"
                                    onclick="window.location='{{ route('outgoing-transactions', $record->id) }}'"
                                    class="group cursor-pointer transition-colors duration-100 hover:bg-emerald-50/40 dark:hover:bg-emerald-900/40"
                                >

                                    {{-- Reference --}}
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-gray-800 dark:text-gray-100 transition-colors group-hover:text-emerald-600">
                                            {{ $record->reference }}
                                        </span>
                                        @if($record->origin_reference)
                                            <p class="truncate text-xs text-gray-400 dark:text-gray-500" title="{{ $record->origin_reference }}">
                                                Origin ref: {{ $record->origin_reference }}
                                            </p>
                                        @endif
                                    </td>


                                    {{-- Subject --}}
                                    <td class="max-w-[320px] px-4 py-3">
                                        <p
                                            class="truncate text-gray-800 dark:text-gray-100"
                                            title="{{ $record->subject }}"
                                        >
                                            {{ $record->subject }}
                                        </p>
                                    </td>


                                    {{-- Author --}}
                                    <td class="px-4 py-3">

                                        <div class="flex items-center gap-2">

                                            <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-[10px] font-semibold uppercase text-emerald-700 dark:text-emerald-300">
                                                {{ strtoupper(substr($record->created_by ?? '?', 0, 1)) }}
                                            </span>

                                            <span
                                                class="max-w-[180px] truncate text-sm text-gray-600 dark:text-gray-300"
                                                title="{{ $record->created_by }}"
                                            >
                                                {{ $record->created_by ?? 'N/A' }}
                                            </span>

                                        </div>

                                    </td>


                                    {{-- Date --}}
                                    <td class="px-4 py-3">

                                        <span
                                            class="text-gray-600 dark:text-gray-300"
                                            title="{{ $date?->format('M d, Y g:i A') }}"
                                        >
                                            {{ $date?->diffForHumans() ?? 'N/A' }}
                                        </span>

                                        @if($isStale)
                                            <span class="mt-0.5 block text-[11px] font-medium text-amber-500 dark:text-amber-400">
                                                {{ $date->format('M d, Y') }}
                                            </span>
                                        @endif

                                    </td>


                                    {{-- RAS audit trail --}}
                                    <td class="px-4 py-3 text-right">

                                        <a
                                            href="{{ route('records-pdf', $record->id) }}"
                                            target="_blank"
                                            onclick="event.stopPropagation()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 shadow-sm transition hover:border-emerald-300 dark:hover:border-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 hover:text-emerald-700 dark:hover:text-emerald-300"
                                        >
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
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                                                />
                                            </svg>

                                            RAS
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-4 py-20 text-center"
                                    >

                                        <svg
                                            class="mx-auto mb-2 size-8 text-gray-300 dark:text-gray-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 7.5l1.5-3h15l1.5 3m-18 0v10.5A1.5 1.5 0 004.5 19.5h15a1.5 1.5 0 001.5-1.5V7.5m-18 0h18M8 12h8"
                                            />
                                        </svg>

                                        <p class="text-sm text-gray-400 dark:text-gray-500">
                                            No records found
                                        </p>

                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- Pagination footer --}}
                <div class="flex flex-col gap-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">

                    {{-- Record count --}}
                    <p class="order-3 text-xs text-gray-400 dark:text-gray-500 sm:order-1">
                        Showing
                        {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }}
                        of
                        {{ $data->total() }}
                    </p>


                    {{-- Pagination --}}
                    <div class="order-1 flex items-center gap-2 sm:order-2">

                        <button
                            type="button"
                            wire:click="previousPage"
                            @disabled($data->onFirstPage())
                            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
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
                                    d="M15.75 19.5L8.25 12l7.5-7.5"
                                />
                            </svg>
                        </button>

                        <span class="px-1 text-xs text-gray-500 dark:text-gray-400">
                            Page {{ $data->currentPage() }}
                            of {{ $data->lastPage() }}
                        </span>

                        <button
                            type="button"
                            wire:click="nextPage"
                            @disabled(!$data->hasMorePages())
                            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
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
                                    d="M8.25 4.5l7.5 7.5-7.5 7.5"
                                />
                            </svg>
                        </button>

                    </div>


                    {{-- Per page --}}
                    <div class="order-2 flex items-center gap-2 sm:order-3">

                        <label class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                            Per page
                        </label>

                        <select
                            wire:model.live="perPage"
                            class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 py-1.5 pl-2 pr-6 text-xs text-gray-700 dark:text-gray-200 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="3">3</option>
                            <option value="5">5</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>
