@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20';
    $error = 'mt-1.5 text-xs font-medium text-red-600 dark:text-red-400';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200';
@endphp

<div class="mx-auto w-full max-w-full px-4 pb-10 lg:px-8">

    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 px-5 py-4">

            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Internal Routing</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Who inside each office is handling this document
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">

                @if ($holder)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 dark:bg-violet-900/30 px-3 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300">
                        <span class="size-1.5 rounded-full bg-violet-500"></span>
                        With {{ $holder->to_name }}
                        @if ($holder->isPending())
                            <span class="font-normal text-violet-500 dark:text-violet-400">(not yet accepted)</span>
                        @endif
                    </span>
                @endif

                @if ($canForward)
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-internal-forward-modal')"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-violet-700"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.688c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.953l-7.108 4.062A1.125 1.125 0 0 1 3 16.81V8.688ZM12.75 8.688c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.953l-7.108 4.062a1.125 1.125 0 0 1-1.683-.977V8.688Z" />
                        </svg>
                        Pass to someone
                    </button>
                @endif

            </div>
        </div>

        {{-- Trail --}}
        @if ($entries->isEmpty())

            <p class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                No internal handoffs yet.
            </p>

        @else

            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($entries as $entry)
                    <li wire:key="internal-{{ $entry->id }}" class="flex flex-wrap items-start gap-3 px-5 py-3.5">

                        <span @class([
                            'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold uppercase',
                            'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' => ! $entry->isPending(),
                            'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' => $entry->isPending(),
                        ])>
                            {{ \Illuminate\Support\Str::substr($entry->to_name, 0, 1) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 dark:text-gray-100">
                                <span class="font-semibold">{{ $entry->from_name ?? 'Someone' }}</span>
                                <span class="text-gray-400 dark:text-gray-500">&rarr;</span>
                                <span class="font-semibold">{{ $entry->to_name }}</span>
                                <span class="ml-1 rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    {{ $entry->office }}
                                </span>
                            </p>

                            @if ($entry->action)
                                <p class="mt-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">{{ $entry->action }}</p>
                            @endif

                            @if ($entry->remarks)
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $entry->remarks }}</p>
                            @endif

                            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                                Passed {{ $entry->created_at?->format('M d, Y g:i A') }}
                                @if ($entry->received_at)
                                    &middot; accepted {{ $entry->received_at->format('M d, Y g:i A') }}
                                @endif
                            </p>
                        </div>

                        <div class="shrink-0">
                            @if ($entry->isPending())
                                @if ((int) $entry->to_user_id === (int) auth()->id() || auth()->user()->isAdmin())
                                    <button
                                        type="button"
                                        wire:click="acknowledge({{ $entry->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="acknowledge({{ $entry->id }})"
                                        class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 transition hover:bg-emerald-600 hover:text-white disabled:opacity-60"
                                    >
                                        Accept
                                    </button>
                                @else
                                    <span class="rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                                        Pending
                                    </span>
                                @endif
                            @else
                                <span class="rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                                    Accepted
                                </span>
                            @endif
                        </div>

                    </li>
                @endforeach
            </ul>

        @endif

    </div>


    {{-- ================================================================= --}}
    {{-- Pass to someone --}}
    {{-- ================================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-internal-forward-modal.window="open = true"
        x-on:close-internal-forward-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="internalForwardModalLabel"
    >

        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>

        <form wire:submit="forward" @click.stop class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">

            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h5 id="internalForwardModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Pass this document on
                </h5>
                <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                    {{ $record?->reference }} &middot; inside {{ auth()->user()->office }}
                </p>
            </div>

            <div class="space-y-4 px-6 py-5">

                <div>
                    <label for="internalRecipient" class="{{ $label }}">
                        Pass to <span class="text-red-500 dark:text-red-400">*</span>
                    </label>

                    <select id="internalRecipient" wire:model="toUserId" class="{{ $input }}">
                        <option value="">-- Select a person --</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}">
                                {{ $person->name }}@if ($person->service) &mdash; {{ $person->service }} @endif
                            </option>
                        @endforeach
                    </select>

                    @error('toUserId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="internalAction" class="{{ $label }}">Action</label>
                    <input id="internalAction" type="text" wire:model="action" placeholder="e.g. For review, For signature, For filing" class="{{ $input }}">
                    @error('action') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <x-remarks-field model="remarks" label="Remarks" :rows="3" accent="blue" />

            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="forward" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="forward">Pass document</span>
                    <span wire:loading wire:target="forward">Saving...</span>
                </button>
            </div>

        </form>
    </div>
</div>
