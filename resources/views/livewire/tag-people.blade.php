<div class="contents">

    {{-- ============================================================= --}}
    {{-- TRIGGER -- matches the other header icon buttons exactly --}}
    {{-- ============================================================= --}}
    <button
        type="button"
        wire:click="openModal"
        aria-label="Tag the personnel this document is about"
        class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-violet-600 hover:bg-violet-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
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
                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"
            />
        </svg>

        {{-- Count badge --}}
        @if ($taggedUsers->isNotEmpty())
            <span
                class="absolute -right-1 -top-1 inline-flex size-4 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white ring-2 ring-white group-hover:bg-violet-900"
            >
                {{ $taggedUsers->count() }}
            </span>
        @endif

        <span
            class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
        >
            Tag People
        </span>

    </button>



    {{-- ============================================================= --}}
    {{-- MODAL --}}
    {{-- ============================================================= --}}
    @if ($showModal)

        <div
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tagPeopleLabel-{{ $record->id }}"
            x-data
            @keydown.escape.window="$wire.closeModal()"
        >

            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-gray-950/50 backdrop-blur-sm"
                wire:click="closeModal"
            ></div>


            {{-- Panel --}}
            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-2xl">

                {{-- Header --}}
                <div class="border-b border-gray-100 dark:border-gray-800 px-6 py-5">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex min-w-0 items-center gap-3">

                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">

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
                                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"
                                    />
                                </svg>

                            </div>


                            <div class="min-w-0">

                                <h2
                                    id="tagPeopleLabel-{{ $record->id }}"
                                    class="text-lg font-bold text-gray-900 dark:text-gray-100"
                                >
                                    Tag Personnel
                                </h2>

                                <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                    Who is {{ $record->reference }} about?
                                </p>

                            </div>

                        </div>


                        <button
                            type="button"
                            wire:click="closeModal"
                            aria-label="Close"
                            class="shrink-0 rounded-lg p-2 text-gray-400 dark:text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
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


                {{-- Body --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">

                    {{-- Office picker --}}
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Office
                    </label>

                    <select
                        wire:model.live="office"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 py-2 pl-3 pr-8 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500"
                    >
                        <option value="">-- Select Office --</option>

                        @foreach ($officeOptions as $name)
                            <option value="{{ $name }}" @selected($office === $name)>{{ $name }}</option>
                        @endforeach
                    </select>


                    {{-- Personnel list --}}
                    <div class="mt-5">

                        <div class="mb-2 flex items-center justify-between">

                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Personnel
                            </p>

                            @if (count($selected) > 0)
                                <span class="rounded-full bg-violet-50 dark:bg-violet-900/30 px-2 py-0.5 text-[11px] font-semibold text-violet-700 dark:text-violet-300">
                                    {{ count($selected) }} selected
                                </span>
                            @endif

                        </div>


                        @if (blank($office))

                            <p class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                                Choose an office to list its personnel.
                            </p>

                        @elseif ($personnel->isEmpty())

                            <p class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                                No personnel found under {{ $office }}.
                            </p>

                        @else

                            <div class="divide-y divide-gray-100 dark:divide-gray-800 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">

                                @foreach ($personnel as $person)

                                    <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition hover:bg-violet-50/60 dark:hover:bg-violet-900/40">

                                        <input
                                            type="checkbox"
                                            value="{{ $person->id }}"
                                            wire:model="selected"
                                            @checked(in_array((string) $person->id, $selected, true))
                                            class="size-4 shrink-0 rounded border-gray-300 dark:border-gray-700 text-violet-600 dark:text-violet-400 focus:ring-violet-500"
                                        >

                                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                            {{ \Illuminate\Support\Str::substr($person->name, 0, 1) }}
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ $person->name }}
                                            </p>

                                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                                {{ $person->service ?: $person->office }}
                                            </p>

                                        </div>

                                    </label>

                                @endforeach

                            </div>

                        @endif

                    </div>


                    {{-- Already tagged, across all offices --}}
                    @if ($taggedUsers->isNotEmpty())

                        <div class="mt-5 border-t border-gray-100 dark:border-gray-800 pt-4">

                            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Currently tagged
                            </p>

                            <div class="flex flex-wrap gap-1.5">

                                @foreach ($taggedUsers as $tagged)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/30 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300">
                                        {{ $tagged->name }}
                                        <span class="text-violet-400 dark:text-violet-500">{{ $tagged->pivot->office ?: $tagged->office }}</span>
                                    </span>
                                @endforeach

                            </div>

                        </div>

                    @endif

                </div>


                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeModal"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                    >
                        Cancel
                    </button>


                    <button
                        type="button"
                        wire:click="saveTags"
                        wire:loading.attr="disabled"
                        wire:target="saveTags"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <svg
                            wire:loading
                            wire:target="saveTags"
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

                        <span wire:loading.remove wire:target="saveTags">Save &amp; Notify</span>
                        <span wire:loading wire:target="saveTags">Saving...</span>

                    </button>

                </div>

            </div>

        </div>

    @endif

</div>
