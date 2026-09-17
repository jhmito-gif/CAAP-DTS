<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ $file['name'] }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $file['kind'] }}</p>
            </div>

            <a href="{{ route('documents.index') }}" class="shrink-0 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                Back to documents
            </a>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-[110rem] px-4 py-6 sm:px-6 lg:px-8"
        x-data="documentPage(@js($file))"
    >
        <div class="flex flex-col gap-4 lg:flex-row">

            {{-- The document, fitted to the space it has --}}
            <div class="min-w-0 flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:!bg-gray-800">

                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-2 text-[11px] dark:border-gray-700">
                    <template x-if="pages > 0">
                        <span class="flex items-center gap-1.5">
                            <button type="button" @click="go(-1)" :disabled="page <= 1"
                                class="rounded border border-gray-200 px-2 py-1 font-semibold disabled:opacity-40 dark:border-gray-700">Prev</button>
                            <span class="text-gray-500">Page <span x-text="page"></span> of <span x-text="pages"></span></span>
                            <button type="button" @click="go(1)" :disabled="page >= pages"
                                class="rounded border border-gray-200 px-2 py-1 font-semibold disabled:opacity-40 dark:border-gray-700">Next</button>
                        </span>
                    </template>

                    <span class="flex items-center gap-1.5">
                        <button type="button" @click="zoom(0.8)" aria-label="Zoom out" class="rounded border border-gray-200 px-2 py-1 font-semibold dark:border-gray-700">&minus;</button>
                        <button type="button" @click="fitToPage()" aria-label="Fit the document"
                            :class="fit ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700'"
                            class="rounded border px-2 py-1 font-semibold">Fit</button>
                        <button type="button" @click="zoom(1.25)" aria-label="Zoom in" class="rounded border border-gray-200 px-2 py-1 font-semibold dark:border-gray-700">+</button>
                    </span>

                    <span class="ml-auto flex items-center gap-3 font-semibold">
                        {{-- The floating window is a convenience on top of this page. --}}
                        <button type="button" @click="openFloating()" class="text-indigo-600 hover:underline dark:text-indigo-400">
                            Open in a floating window
                        </button>
                        @if ($file['download_url'])
                            <a href="{{ $file['download_url'] }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Download</a>
                        @endif
                    </span>
                </div>

                <div class="min-h-[70vh] bg-gray-100 p-4 text-center dark:bg-gray-900" x-ref="stage">
                    <p x-show="loading" class="py-24 text-xs text-gray-500">Opening…</p>
                    <p x-show="error" x-cloak class="py-24 text-xs font-semibold text-rose-600" x-text="error"></p>

                    @if (str_starts_with($file['mime'], 'image/'))
                        <img src="{{ $file['view_url'] }}" alt="{{ $file['name'] }}" class="mx-auto block max-w-full bg-white shadow">
                    @elseif ($file['mime'] === 'application/pdf')
                        <canvas x-ref="canvas" class="mx-auto block bg-white shadow"></canvas>
                    @else
                        <div class="py-24">
                            <p class="text-xs text-gray-500">This kind of file cannot be shown here.</p>
                            @if ($file['download_url'])
                                <a href="{{ $file['download_url'] }}" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:underline">Download it</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Everything known about it --}}
            <aside class="w-full shrink-0 space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:!bg-gray-800 lg:w-80"
                   aria-label="Document details">

                <div>
                    <p class="break-words text-sm font-bold text-gray-900 dark:text-gray-100">{{ $file['name'] }}</p>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $file['kind'] }}</p>
                </div>

                <dl class="space-y-1.5">
                    @foreach ($file['facts'] as $fact)
                        <div class="grid grid-cols-3 gap-2">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $fact['label'] }}</dt>
                            <dd class="col-span-2 break-words text-[11px] text-gray-700 dark:text-gray-200">{{ $fact['value'] ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if (count($file['signatures']) || $file['awaiting_signatures'])
                    <div class="border-t border-gray-100 pt-2 dark:border-gray-700">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">Signatures</p>

                        @foreach ($file['signatures'] as $signature)
                            <div class="mb-1.5 rounded-md bg-emerald-50 px-2 py-1.5 dark:bg-emerald-900/20">
                                <p class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300">{{ $signature['signer'] }}</p>
                                <p class="text-[10px] text-emerald-700/80 dark:text-emerald-400/80">{{ $signature['office'] }}</p>
                                <p class="text-[10px] text-gray-500">{{ $signature['signed_at'] }}</p>
                                <p class="font-mono text-[10px] text-gray-500">{{ $signature['code'] }}</p>
                                @if (count($signature['pages']) > 1)
                                    <p class="text-[10px] text-gray-500">Pages {{ implode(', ', $signature['pages']) }}</p>
                                @endif
                            </div>
                        @endforeach

                        @if ($file['awaiting_signatures'])
                            <p class="text-[11px] text-amber-700 dark:text-amber-400">{{ $file['awaiting_signatures'] }} still to sign</p>
                        @endif
                    </div>
                @endif

                <div class="border-t border-gray-100 pt-2 dark:border-gray-700">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">Searching</p>
                    <p class="text-[11px] text-gray-600 dark:text-gray-300">{{ $file['reading']['summary'] }}</p>
                </div>

                @if ($file['record_url'])
                    <div class="border-t border-gray-100 pt-2 text-[11px] font-semibold dark:border-gray-700">
                        <a href="{{ $file['record_url'] }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Open the record</a>
                    </div>
                @endif
            </aside>
        </div>
    </div>

    {{-- The floating window, for reading this alongside something else. --}}
    @include('partials.document-windows')

    @vite('resources/js/viewer.js')
</x-app-layout>
