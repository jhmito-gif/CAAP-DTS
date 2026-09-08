<div class="w-full min-h-screen bg-gray-50/60 flex flex-col pt-6">

    <section class="mt-2">

        @if($latest)
            <div class="mx-auto max-w-full px-4 lg:px-8 mb-5">
                <div class="relative flex items-center gap-4 overflow-hidden rounded-lg border border-gray-200 bg-white pl-5 pr-5 py-3 shadow-sm">
                    <span class="absolute inset-y-0 left-0 w-1 bg-blue-600"></span>
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="grid min-w-0 flex-1 grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-3">
                        <div>
                            <p class="text-[11px] font-medium text-gray-400">Latest record</p>
                            <p class="truncate text-sm font-semibold text-blue-700">{{ $latest->reference }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium text-gray-400">Subject</p>
                            <p class="truncate text-sm text-gray-700">{{ $latest->subject }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-gray-400">Received</p>
                            <p class="text-sm text-gray-700">{{ $latest->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-full px-4 lg:px-8">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-blue-700"
                        @click="$dispatch('open-modal')">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Incoming
                    </button>
                    <span class="hidden text-sm text-gray-400 sm:inline">
                        {{ $data->total() }} {{ Str::plural('record', $data->total()) }}
                    </span>
                </div>

                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-9 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Search records...">
                    <button type="button" x-data
                        x-show="$wire.search && $wire.search.length > 0" x-cloak
                        @click="$wire.set('search', '')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        aria-label="Clear search">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead class="sticky top-0 z-10 border-b border-gray-200 bg-gray-50/95 text-[11px] font-semibold uppercase tracking-wide text-gray-500 backdrop-blur">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Routing</th>
                                <th class="px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($data as $transaction)
                                @php
                                    $record = $transaction->record;
                                    $date = $record?->created_at;
                                    $statusKey = strtolower($transaction->status ?? '');

                                    // Adjust these keys to match your real status values
                                    $styles = [
                                        'pending'     => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
                                        'in progress' => ['bg-blue-50 text-blue-700', 'bg-blue-500'],
                                        'forwarded'   => ['bg-indigo-50 text-indigo-700', 'bg-indigo-500'],
                                        'completed'   => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                                        'closed'      => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                                        'returned'    => ['bg-rose-50 text-rose-700', 'bg-rose-500'],
                                        'on hold'     => ['bg-gray-100 text-gray-600', 'bg-gray-400'],
                                    ];
                                    [$badge, $dot] = $styles[$statusKey] ?? ['bg-gray-100 text-gray-600', 'bg-gray-400'];

                                    // Flags records untouched for 5+ days that aren't done
                                    $isStale = $date && !in_array($statusKey, ['completed', 'closed']) && $date->diffInDays(now()) >= 5;
                                @endphp
                                <tr wire:key="{{ $record?->id ?? $transaction->id }}"
                                    @if($record) onclick="window.location='{{ route('show-transactions', $record->id) }}'" @endif
                                    class="group {{ $record ? 'cursor-pointer' : '' }} transition-colors duration-100 hover:bg-blue-50/40">
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-gray-800 group-hover:text-blue-600">
                                            {{ $record?->reference ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="max-w-[260px] px-4 py-3">
                                        <p class="truncate text-gray-800" title="{{ $record?->subject }}">{{ $record?->subject ?? 'N/A' }}</p>
                                        <p class="truncate text-xs text-gray-400">{{ $transaction->office }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">
                                            <span class="size-1.5 rounded-full {{ $dot }}"></span>
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                            <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-600">
                                                {{ strtoupper(substr($transaction->recieved_by ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="max-w-[90px] truncate">{{ $transaction->recieved_by }}</span>
                                            <svg class="size-3 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                            <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[10px] font-semibold text-blue-600">
                                                {{ strtoupper(substr($transaction->forwarded_by ?? '?', 0, 1)) }}
                                            </span>
                                            <span class="max-w-[90px] truncate">{{ $transaction->forwarded_by }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-gray-600" title="{{ $date?->format('M d, Y g:i A') }}">
                                            {{ $date?->diffForHumans() ?? 'N/A' }}
                                        </span>
                                        @if($isStale)
                                            <span class="mt-0.5 block text-[11px] font-medium text-rose-500">Awaiting action</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-20 text-center">
                                        <svg class="mx-auto mb-2 size-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5l1.5-3h15l1.5 3m-18 0v10.5A1.5 1.5 0 004.5 19.5h15a1.5 1.5 0 001.5-1.5V7.5m-18 0h18M8 12h8" />
                                        </svg>
                                        <p class="text-sm text-gray-400">No records found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

              <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
    <p class="order-3 text-xs text-gray-400 sm:order-1">
        Showing {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }} of {{ $data->total() }}
    </p>

    <div class="order-1 flex items-center gap-2 sm:order-2">
        <button wire:click="previousPage" @disabled($data->onFirstPage())
            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>
        <span class="px-1 text-xs text-gray-500">
            Page {{ $data->currentPage() }} of {{ $data->lastPage() }}
        </span>
        <button wire:click="nextPage" @disabled(!$data->hasMorePages())
            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>

    <div class="order-2 flex items-center gap-2 sm:order-3">
        <label class="whitespace-nowrap text-xs text-gray-500">Per page</label>
        <select wire:model.live="perPage"
            class="rounded-md border border-gray-300 bg-white py-1.5 pl-2 pr-6 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
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