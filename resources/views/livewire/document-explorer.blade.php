@php
    // Shared classes, so the markup below stays about structure.
    $toolButton = 'inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-indigo-900/30';
    $column = 'px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500';

    /*
     * What the menu, dragging and the keyboard need to know about an entry is
     * carried on the row itself. Passing it through x-data instead would change
     * that expression on every render, and Alpine would rebuild the component
     * -- losing the open menu and the selection anchor with it.
     */
    $entryAttributes = fn ($entry) => collect([
        'data-entry' => $entry->key,
        'data-type' => $entry->type,
        'data-kind' => $entry->kind ?? 'other',
        'data-name' => $entry->name,
        'data-meta' => $entry->meta,
        'data-location' => $entry->location,
        'data-view' => $entry->view_url ?? null,
        'data-download' => $entry->download_url ?? null,
        'data-record' => $entry->record_url ?? null,
        'data-movable' => $entry->can_move ? '1' : '0',
    ])->filter(fn ($value) => $value !== null)
        ->map(fn ($value, $name) => $name . '="' . e($value) . '"')
        ->implode(' ');
@endphp

<div
    class="min-h-screen bg-gray-50/70 dark:bg-gray-900"
    x-data="documentExplorer()"
    data-can-organise="{{ $canOrganise ? '1' : '0' }}"
    @keydown.window="onKey($event)"
    @dragover.prevent="dragOverWindow($event)"
    @dragleave="dragLeaveWindow($event)"
    @drop.prevent="dropFiles($event)"
>
    <div class="mx-auto max-w-[110rem] px-4 py-6 sm:px-6 lg:px-8">

        {{-- ========================= Toolbar ========================= --}}
        <div class="mb-3 flex flex-wrap items-center gap-2">

            @if ($canOrganise)
                {{-- Made straight away and named in place, the way Explorer does it. --}}
                <button type="button" wire:click="createFolder" class="{{ $toolButton }}">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                    New folder
                </button>
            @endif

            <button type="button" class="{{ $toolButton }}" @click="$refs.picker.click()" @disabled(! $canUpload)>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                Upload
            </button>

            {{-- Sent in pieces by the uploader below, not by Livewire. --}}
            <input type="file" multiple x-ref="picker" @change="queueFiles([...$event.target.files]); $event.target.value = ''" class="hidden" aria-label="Files to upload">

            <button
                type="button"
                class="{{ $toolButton }}"
                wire:click="deleteSelected"
                wire:confirm="Delete the selected items? Folders must be empty first."
                @disabled(empty($selected))
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                Delete
            </button>

            <div class="ml-auto flex items-center gap-2">

                <div class="flex items-center">
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="{{ $scope === 'everywhere' ? 'Search names and contents' : 'Filter this folder' }}"
                        aria-label="{{ $scope === 'everywhere' ? 'Search names and contents' : 'Filter this folder' }}"
                        class="w-52 rounded-l-md border-gray-200 bg-white px-3 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:!bg-gray-800 dark:text-gray-100"
                    >
                    <select
                        wire:model.live="scope"
                        aria-label="Where to search"
                        class="-ml-px rounded-r-md border-gray-200 bg-white py-1.5 pl-2 pr-7 text-xs shadow-sm dark:border-gray-700 dark:!bg-gray-800 dark:text-gray-100"
                    >
                        <option value="everywhere">everywhere</option>
                        <option value="folder">this folder</option>
                    </select>
                </div>

                @if ($offices->count() > 1)
                    <select
                        aria-label="Office library"
                        class="rounded-md border-gray-200 bg-white px-2 py-1.5 text-xs shadow-sm dark:border-gray-700 dark:!bg-gray-800 dark:text-gray-100"
                        wire:change="switchOffice($event.target.value)"
                    >
                        @foreach ($offices as $name)
                            <option value="{{ $name }}" @selected($name === $office)>{{ $name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="inline-flex overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('view', 'details')" aria-label="Details view"
                        class="px-2 py-1.5 text-xs font-semibold {{ $view === 'details' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 dark:!bg-gray-800 dark:text-gray-300' }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" /></svg>
                    </button>
                    <button type="button" wire:click="$set('view', 'tiles')" aria-label="Tiles view"
                        class="px-2 py-1.5 text-xs font-semibold {{ $view === 'tiles' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 dark:!bg-gray-800 dark:text-gray-300' }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ===================== Explorer window ===================== --}}
        <div class="flex min-h-[70vh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:!bg-gray-800">

            {{-- ------------------------- Tree ------------------------- --}}
            <aside class="hidden w-60 shrink-0 border-r border-gray-100 bg-gray-50/60 p-2 dark:border-gray-700 dark:bg-gray-900/40 md:block" aria-label="Folders">

                <p class="px-2 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Quick access</p>

                <button type="button" wire:click="open('recent')"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium {{ $location === 'recent' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    Recent
                </button>

                <button type="button" wire:click="open('records')"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium {{ str_starts_with($location, 'record') ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    Records
                </button>

                <p class="px-2 pb-1 pt-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $office }} Library</p>

                <button
                    type="button"
                    wire:click="open('library')"
                    @dragover.prevent="dragFolder = 'root'"
                    @dragleave="dragFolder = null"
                    @drop.prevent.stop="dropOnFolder(null)"
                    :class="dragFolder === 'root' ? 'ring-2 ring-indigo-400' : ''"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium {{ $location === 'library' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                >
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                    All files
                </button>

                @foreach ($tree as $node)
                    <button
                        type="button"
                        wire:key="tree-{{ $node->id }}"
                        wire:click="open('{{ $node->location }}')"
                        @dragover.prevent="dragFolder = {{ $node->id }}"
                        @dragleave="dragFolder = null"
                        @drop.prevent.stop="dropOnFolder({{ $node->id }})"
                        :class="dragFolder === {{ $node->id }} ? 'ring-2 ring-indigo-400' : ''"
                        style="padding-left: {{ 8 + $node->depth * 12 }}px"
                        class="flex w-full items-center gap-2 rounded-md py-1.5 pr-2 text-left text-xs font-medium {{ $location === $node->location ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                    >
                        <svg class="size-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M2.25 6A2.25 2.25 0 0 1 4.5 3.75h4.129a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H19.5A2.25 2.25 0 0 1 21.75 9v9a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 2.25 18V6Z" /></svg>
                        <span class="truncate">{{ $node->name }}</span>
                        @if ($node->files)
                            <span class="ml-auto shrink-0 text-[10px] text-gray-400">{{ $node->files }}</span>
                        @endif
                    </button>
                @endforeach
            </aside>

            {{-- ----------------------- Contents ----------------------- --}}
            <section class="flex min-w-0 flex-1 flex-col">

                {{-- Breadcrumbs --}}
                <div class="flex flex-wrap items-center gap-1 border-b border-gray-100 px-4 py-2.5 text-xs dark:border-gray-700">
                    @foreach ($trail as $index => $crumb)
                        @if ($index > 0)
                            <span class="text-gray-300 dark:text-gray-600">/</span>
                        @endif
                        <button type="button" wire:click="open('{{ $crumb['location'] }}')"
                            class="rounded px-1.5 py-0.5 font-semibold {{ $index === count($trail) - 1 ? 'text-gray-800 dark:text-gray-100' : 'text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30' }}">
                            {{ $crumb['label'] }}
                        </button>
                    @endforeach

                </div>

                {{-- Files being sent, a piece at a time --}}
                <div x-show="queued.length > 0" x-cloak class="border-b border-indigo-100 bg-indigo-50/60 px-4 py-2.5 dark:border-indigo-900 dark:bg-indigo-900/20">
                    <p class="text-xs font-semibold text-indigo-900 dark:text-indigo-200">
                        <span x-text="queued.length"></span> <span x-text="queued.length === 1 ? 'file' : 'files'"></span>
                        <span x-show="! sending">ready to upload{{ $folder ? " to {$folder->name}" : '' }}</span>
                        <span x-show="sending" x-cloak>uploading{{ $folder ? " to {$folder->name}" : '' }}…</span>
                    </p>

                    <ul class="mt-1.5 space-y-1">
                        <template x-for="(file, index) in queued" :key="file.id">
                            <li class="flex items-center gap-2 rounded-md bg-white px-2 py-1 text-[11px] dark:!bg-gray-800">
                                <span class="max-w-[220px] truncate" x-text="file.name"></span>
                                <span class="text-gray-400" x-text="file.sizeLabel"></span>

                                <span class="ml-auto flex items-center gap-2">
                                    <span x-show="file.state !== 'waiting'" class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <span class="block h-full rounded-full transition-all duration-200"
                                              :class="file.state === 'failed' ? 'bg-rose-500' : (file.state === 'done' ? 'bg-emerald-500' : 'bg-indigo-600')"
                                              :style="`width: ${file.percent}%`"></span>
                                    </span>
                                    <span class="w-24 text-right" :class="file.state === 'failed' ? 'text-rose-600' : 'text-gray-500'" x-text="file.note"></span>
                                    <button type="button" x-show="! sending" @click="queued.splice(index, 1)" :aria-label="`Remove ${file.name}`" class="text-gray-400 hover:text-rose-600">&times;</button>
                                </span>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-2 flex items-center gap-2">
                        <select x-model="uploadCategory" :disabled="sending" aria-label="Category" class="rounded-md border-gray-200 bg-white px-2 py-1 text-[11px] dark:border-gray-700 dark:!bg-gray-800 dark:text-gray-100">
                            <option value="">No category</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        <button type="button" @click="sendFiles()" :disabled="sending"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="! sending">Upload here</span>
                            <span x-show="sending" x-cloak>Sending…</span>
                        </button>

                        <span x-show="uploadError" x-cloak class="text-[11px] font-semibold text-rose-600" x-text="uploadError"></span>
                    </div>

                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                        Sent in 2 MB pieces, so a large scan survives a stall. Each file is read for searching as soon as it lands.
                    </p>
                </div>

                @error('newFolderName') <p class="border-b border-rose-100 bg-rose-50 px-4 py-2 text-[11px] font-semibold text-rose-700 dark:bg-rose-900/20">{{ $message }}</p> @enderror

                {{-- Entries --}}
                <div class="relative min-h-0 flex-1 overflow-auto" wire:loading.class="opacity-60">

                    @if ($searching)
                        {{-- Found by what the documents say --}}
                        <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-2 text-[11px] text-gray-500 dark:border-gray-700">
                            <span>
                                {{ $found->count() }} {{ \Illuminate\Support\Str::plural('document', $found->count()) }} containing “{{ $search }}”
                                @if ($unread > 0)
                                    &middot; {{ $unread }} {{ \Illuminate\Support\Str::plural('file', $unread) }} not read yet
                                @endif
                            </span>

                            @if ($unread > 0 && $canStartReading)
                                <button type="button" wire:click="readEverything" class="ml-auto font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                    Read them all
                                </button>
                            @endif
                        </div>

                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($found as $hit)
                                <li wire:key="hit-{{ $hit->key }}" class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold {{ $hit->name_hidden ? 'italic text-rose-600' : 'text-gray-800 dark:text-gray-100' }}">
                                                {{ $hit->name }}
                                            </p>
                                            <p class="text-[11px] text-gray-400">
                                                {{ $hit->where }}
                                                <span class="ml-1 rounded bg-gray-100 px-1 py-0.5 text-[9px] font-bold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ $hit->matched }}
                                                </span>
                                            </p>
                                            @if ($hit->snippet)
                                                <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">{{ $hit->snippet }}</p>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 items-center gap-2 text-[11px] font-semibold">
                                            @if ($hit->view_url)
                                                <a href="{{ route('files.show', ['key' => $hit->key]) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Open</a>
                                                <button
                                                    type="button"
                                                    @click="openFound(@js($hit->key), @js($hit->name), @js($hit->view_url), @js($hit->kind), @js($hit->where))"
                                                    title="Open in a floating window"
                                                    class="text-gray-500 hover:underline"
                                                >Window</button>
                                            @endif
                                            @if ($hit->record_url)
                                                <a href="{{ $hit->record_url }}" class="text-gray-500 hover:underline">Record</a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @elseif ($entries->isEmpty())
                        <p class="px-4 py-16 text-center text-sm text-gray-400">
                            This folder is empty. @if ($canUpload) Drop files here to upload them. @endif
                        </p>
                    @elseif ($view === 'details')
                        <table class="w-full text-left text-sm">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900/60">
                                <tr>
                                    <th class="{{ $column }} w-8"></th>
                                    <th class="{{ $column }}"><button type="button" wire:click="setSort('name')" class="hover:text-indigo-600">Name</button></th>
                                    <th class="{{ $column }} hidden sm:table-cell"><button type="button" wire:click="setSort('modified')" class="hover:text-indigo-600">Date</button></th>
                                    <th class="{{ $column }} hidden md:table-cell"><button type="button" wire:click="setSort('type')" class="hover:text-indigo-600">Type</button></th>
                                    <th class="{{ $column }} hidden sm:table-cell"><button type="button" wire:click="setSort('size')" class="hover:text-indigo-600">Size</button></th>
                                    <th class="{{ $column }} w-24"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($entries as $entry)
                                    <tr
                                        wire:key="entry-{{ $entry->key }}"
                                        {!! $entryAttributes($entry) !!}
                                        draggable="{{ $entry->can_move || ($entry->type === 'file' && ($entry->source ?? '') === 'attachment') ? 'true' : 'false' }}"
                                        @dragstart="startDrag($event, '{{ $entry->key }}')"
                                        @if ($entry->type === 'folder' && $entry->id)
                                            @dragover.prevent="dragFolder = {{ $entry->id }}"
                                            @dragleave="dragFolder = null"
                                            @drop.prevent.stop="dropOnFolder({{ $entry->id }})"
                                        @endif
                                        @click="clickEntry($event, '{{ $entry->key }}')"
                                        @contextmenu.prevent="showMenu($event, '{{ $entry->key }}')"
                                        @dblclick="openEntry('{{ $entry->key }}')"
                                        class="cursor-default select-none {{ in_array($entry->key, $selected, true) ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40' }}"
                                        @if ($entry->type === 'folder' && $entry->id)
                                            :class="dragFolder === {{ $entry->id }} ? 'ring-2 ring-inset ring-indigo-400' : ''"
                                        @endif
                                    >
                                        <td class="px-3 py-2">
                                            @if ($entry->type === 'folder')
                                                <svg class="size-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M2.25 6A2.25 2.25 0 0 1 4.5 3.75h4.129a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H19.5A2.25 2.25 0 0 1 21.75 9v9a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 2.25 18V6Z" /></svg>
                                            @else
                                                <span class="flex size-5 items-center justify-center rounded bg-gray-100 text-[8px] font-bold text-gray-500 dark:bg-gray-900 dark:text-gray-400">{{ $entry->extension ?? 'FILE' }}</span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2">
                                            @if ($renamingKey === $entry->key)
                                                <input
                                                    type="text"
                                                    wire:model="renameValue"
                                                    wire:keydown.enter="saveRename"
                                                    wire:keydown.escape="cancelRename"
                                                    wire:blur="saveRename"
                                                    autofocus
                                                    aria-label="New name"
                                                    class="w-full rounded border-indigo-300 px-2 py-1 text-xs dark:!bg-gray-900 dark:text-gray-100"
                                                >
                                            @else
                                                <p class="truncate font-medium {{ ($entry->name_hidden ?? false) ? 'italic text-rose-600 dark:text-rose-400' : 'text-gray-800 dark:text-gray-100' }}">
                                                    {{ $entry->name }}
                                                    @if (($entry->source ?? '') === 'shortcut')
                                                        <span class="ml-1 rounded bg-sky-50 px-1 py-0.5 text-[9px] font-bold uppercase text-sky-600 dark:bg-sky-900/30 dark:text-sky-300">link</span>
                                                    @endif
                                                </p>
                                                <p class="truncate text-[11px] text-gray-400">{{ $entry->meta }}</p>
                                            @endif
                                        </td>

                                        <td class="hidden px-3 py-2 text-xs text-gray-500 sm:table-cell">{{ $entry->modified?->format('d M Y g:i A') }}</td>
                                        <td class="hidden px-3 py-2 text-xs text-gray-500 md:table-cell">{{ $entry->type === 'folder' ? 'Folder' : ($entry->extension ?? 'File') }}</td>
                                        <td class="hidden px-3 py-2 text-xs text-gray-500 sm:table-cell">{{ $entry->size ? \App\Models\Document::formatBytes($entry->size) : '' }}</td>

                                        <td class="px-3 py-2">
                                            <div class="flex items-center gap-1">
                                                @if (($entry->view_url ?? null))
                                                    {{-- Straight to the file's own page; the window is on the menu. --}}
                                                    <a href="{{ route('files.show', ['key' => $entry->key]) }}" @click.stop title="Open" class="rounded p-1 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                    </a>
                                                @endif
                                                @if (($entry->record_url ?? null))
                                                    <a href="{{ $entry->record_url }}" @click.stop title="Open the record" class="rounded p-1 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                                    </a>
                                                @endif
                                                @if ($canOrganise && $entry->can_move)
                                                    <button type="button" wire:click="startRename('{{ $entry->key }}')" @click.stop title="Rename" class="rounded p-1 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        {{-- Tiles --}}
                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
                            @foreach ($entries as $entry)
                                <button
                                    type="button"
                                    wire:key="tile-{{ $entry->key }}"
                                    {!! $entryAttributes($entry) !!}
                                    draggable="{{ $entry->can_move || ($entry->type === 'file' && ($entry->source ?? '') === 'attachment') ? 'true' : 'false' }}"
                                    @dragstart="startDrag($event, '{{ $entry->key }}')"
                                    @if ($entry->type === 'folder' && $entry->id)
                                        @dragover.prevent="dragFolder = {{ $entry->id }}"
                                        @dragleave="dragFolder = null"
                                        @drop.prevent.stop="dropOnFolder({{ $entry->id }})"
                                    @endif
                                    @click="clickEntry($event, '{{ $entry->key }}')"
                                    @contextmenu.prevent="showMenu($event, '{{ $entry->key }}')"
                                    @dblclick="openEntry('{{ $entry->key }}')"
                                    class="flex flex-col items-center gap-2 rounded-lg border p-3 text-center transition {{ in_array($entry->key, $selected, true) ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/40' }}"
                                >
                                    @if ($entry->type === 'folder')
                                        <svg class="size-12 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M2.25 6A2.25 2.25 0 0 1 4.5 3.75h4.129a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H19.5A2.25 2.25 0 0 1 21.75 9v9a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 2.25 18V6Z" /></svg>
                                    @else
                                        <span class="flex size-12 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-500 dark:bg-gray-900 dark:text-gray-400">{{ $entry->extension ?? 'FILE' }}</span>
                                    @endif
                                    <span class="line-clamp-2 text-[11px] font-medium {{ ($entry->name_hidden ?? false) ? 'italic text-rose-600' : 'text-gray-700 dark:text-gray-200' }}">{{ $entry->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Drop overlay --}}
                    <div x-show="dropping" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center bg-indigo-500/10">
                        <p class="rounded-lg border-2 border-dashed border-indigo-400 bg-white px-6 py-4 text-sm font-semibold text-indigo-700 shadow dark:!bg-gray-800 dark:text-indigo-300">
                            Drop files to upload{{ $folder ? " to {$folder->name}" : '' }}
                        </p>
                    </div>
                </div>

                {{-- Status bar --}}
                <div class="flex items-center gap-3 border-t border-gray-100 px-4 py-1.5 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <span>{{ $entries->count() }} {{ \Illuminate\Support\Str::plural('item', $entries->count()) }}</span>
                    @if (count($selected))
                        <span class="text-indigo-600 dark:text-indigo-400">{{ count($selected) }} selected</span>
                    @endif
                    <span class="ml-auto">{{ $office }}{{ $folder ? ' ' . $folder->path : '' }}</span>
                </div>
            </section>
        </div>

        <p class="mt-2 text-[11px] text-gray-400">
            Double-click to open a file on its own page, or right-click to open it in a floating window.
            Drag onto a folder to move; drag a record file into a folder to file a link to it.
            F2 renames, Delete removes.
            @unless ($canOrganise)
                You can upload to this library and open what is in it; creating and rearranging folders is for its document managers.
            @endunless
        </p>
    </div>

    {{-- Sharing and access --}}
    @if ($sharing)
        <div
            class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/40 p-4"
            wire:key="sharing-{{ $sharing->id }}"
            @keydown.escape.window="$wire.closeSharing()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sharingLabel"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-2xl dark:!bg-gray-800" @click.outside="$wire.closeSharing()">

                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <div class="min-w-0">
                        <h2 id="sharingLabel" class="truncate text-sm font-bold text-gray-900 dark:text-gray-100">Sharing “{{ $sharing->name }}”</h2>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $sharing->office }}{{ $sharing->path }} &middot; what is granted here applies to everything inside.
                        </p>
                    </div>
                    <button type="button" wire:click="closeSharing" aria-label="Close sharing" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <div class="space-y-4 px-5 py-4">

                    {{-- Closed or open --}}
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <button
                            type="button"
                            wire:click="toggleRestricted"
                            role="switch"
                            aria-checked="{{ $sharing->is_restricted ? 'true' : 'false' }}"
                            class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full transition {{ $sharing->is_restricted ? 'bg-rose-600' : 'bg-gray-300 dark:bg-gray-600' }}"
                        >
                            <span class="inline-block size-3.5 rounded-full bg-white transition {{ $sharing->is_restricted ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
                        </button>
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold text-gray-800 dark:text-gray-100">Closed folder</span>
                            <span class="block text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $sharing->is_restricted
                                    ? 'Only the people and offices listed below can see inside. ' . $sharing->office . ' staff cannot.'
                                    : $sharing->office . ' staff can see inside, as with any folder in their library.' }}
                            </span>
                        </span>
                    </label>

                    {{-- Existing grants --}}
                    <div>
                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">Shared with</p>

                        @if ($sharingPolicies->isEmpty())
                            <p class="rounded-lg bg-gray-50 px-3 py-2.5 text-[11px] text-gray-500 dark:bg-gray-900/40">
                                Nobody outside {{ $sharing->office }} yet.
                            </p>
                        @else
                            <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                                @foreach ($sharingPolicies as $policy)
                                    <li wire:key="policy-{{ $policy->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                                        <span class="min-w-0">
                                            <span class="block truncate text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $policy->describe() }}</span>
                                            <span class="text-[10px] uppercase tracking-wide text-gray-400">{{ $policy->subject_type }}</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $policy->level }}</span>
                                            <button type="button" wire:click="removePolicy({{ $policy->id }})" aria-label="Remove {{ $policy->describe() }}" class="rounded p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                                            </button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Add a grant --}}
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Give access to</p>

                        <div class="flex flex-wrap items-start gap-2">
                            <select wire:model.live="shareSubjectType" aria-label="Who" class="rounded-md border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700 dark:!bg-gray-900 dark:text-gray-100">
                                <option value="office">An office</option>
                                <option value="user">A person</option>
                                <option value="everyone">Everyone signed in</option>
                            </select>

                            @if ($shareSubjectType === 'office')
                                <select wire:model="shareSubject" aria-label="Office" class="min-w-[10rem] rounded-md border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700 dark:!bg-gray-900 dark:text-gray-100">
                                    <option value="">Choose an office…</option>
                                    @foreach ($officeOptions as $name)
                                        <option value="{{ $name }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            @elseif ($shareSubjectType === 'user')
                                <select wire:model="shareSubject" aria-label="Person" class="min-w-[12rem] rounded-md border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700 dark:!bg-gray-900 dark:text-gray-100">
                                    <option value="">Choose a person…</option>
                                    @foreach ($peopleOptions as $person)
                                        <option value="{{ $person->id }}">{{ $person->name }} ({{ $person->office }})</option>
                                    @endforeach
                                </select>
                            @endif

                            <select wire:model="shareLevel" aria-label="Level" class="rounded-md border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700 dark:!bg-gray-900 dark:text-gray-100">
                                <option value="view">can view</option>
                                <option value="edit">can add files</option>
                                <option value="manage">can manage</option>
                            </select>

                            <button type="button" wire:click="addPolicy" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Share</button>
                        </div>

                        @error('shareSubject') <p class="mt-1.5 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                        @error('shareSubjectType') <p class="mt-1.5 text-[11px] font-semibold text-rose-600">{{ $message }}</p> @enderror

                        <p class="mt-2 text-[11px] text-gray-400">
                            A grant here never opens a confidential record file: those keep their own rules.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Right-click menu --}}
    <div
        wire:ignore
        x-show="menu.open"
        x-cloak
        style="display: none"
        @click.outside="menu.open = false"
        @keydown.escape.window="menu.open = false"
        {{-- An object, so the binding merges with the display x-show controls
             rather than replacing the whole style attribute. --}}
        :style="{ top: menu.y + 'px', left: menu.x + 'px' }"
        class="fixed z-50 w-52 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 text-xs shadow-xl dark:border-gray-700 dark:!bg-gray-800"
        role="menu"
        aria-label="Item actions"
    >
        <button type="button" role="menuitem" @click="openEntry(menu.key); menu.open = false"
            class="block w-full px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Open</button>

        <template x-if="entry(menu.key)?.type === 'file' && entry(menu.key)?.view">
            <button type="button" role="menuitem" @click="openFloating(menu.key); menu.open = false"
                class="block w-full px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Open in a floating window</button>
        </template>

        <template x-if="entry(menu.key)?.download">
            <a :href="entry(menu.key)?.download" @click="menu.open = false" role="menuitem"
                class="block px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Download</a>
        </template>

        <template x-if="entry(menu.key)?.record">
            <a :href="entry(menu.key)?.record" @click="menu.open = false" role="menuitem"
                class="block px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Go to the record</a>
        </template>

        <template x-if="entry(menu.key)?.type === 'file'">
            <button type="button" role="menuitem" @click="$wire.readNow(menu.key); menu.open = false"
                class="block w-full px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">
                Read for searching
            </button>
        </template>

        <template x-if="canOrganise && entry(menu.key)?.type === 'folder' && entry(menu.key)?.movable">
            <div>
                <hr class="my-1 border-gray-100 dark:border-gray-700">
                <button type="button" role="menuitem" @click="$wire.shareFolder(Number(menu.key.split('-')[1])); menu.open = false"
                    class="block w-full px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Sharing and access…</button>
            </div>
        </template>

        <template x-if="canOrganise && entry(menu.key)?.movable">
            <div>
                <hr class="my-1 border-gray-100 dark:border-gray-700">
                <button type="button" role="menuitem" @click="$wire.startRename(menu.key); menu.open = false"
                    class="block w-full px-3 py-1.5 text-left font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-indigo-900/30">Rename<span class="ml-2 text-[10px] text-gray-400">F2</span></button>
            </div>
        </template>

        <template x-if="canOrganise">
            <button type="button" role="menuitem" @click="remove(); menu.open = false"
                class="block w-full px-3 py-1.5 text-left font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete<span class="ml-2 text-[10px] text-rose-300">Del</span></button>
        </template>
    </div>
</div>

@script
<script>
    Alpine.data('documentExplorer', () => ({
        dragFolder: null,
        dropping: false,
        dragged: [],
        menu: { open: false, x: 0, y: 0, key: null },
        anchor: null,

        // Files waiting to be sent, and how far each has got.
        queued: [],
        sending: false,
        uploadCategory: '',
        uploadError: '',

        /** Entries are read from the rows, which Livewire keeps up to date. */
        entry(key) {
            if (! key) return null

            let row = this.$root.querySelector(`[data-entry="${CSS.escape(key)}"]`)

            if (! row) return null

            return {
                key,
                name: row.dataset.name,
                type: row.dataset.type,
                kind: row.dataset.kind,
                meta: row.dataset.meta,
                location: row.dataset.location || null,
                view: row.dataset.view || null,
                download: row.dataset.download || null,
                record: row.dataset.record || null,
                movable: row.dataset.movable === '1',
            }
        },

        get order() {
            return [...this.$root.querySelectorAll('[data-entry]')].map((row) => row.dataset.entry)
        },

        get canOrganise() {
            return this.$root.dataset.canOrganise === '1'
        },

        /** Plain click selects; ctrl adds; shift takes the run between. */
        clickEntry(event, key) {
            this.menu.open = false

            if (event.shiftKey && this.anchor) {
                let from = this.order.indexOf(this.anchor)
                let to = this.order.indexOf(key)

                if (from > -1 && to > -1) {
                    let [start, end] = from < to ? [from, to] : [to, from]

                    // Sent to the server, not deferred: the rows and the
                    // status bar are rendered there.
                    this.$wire.set('selected', this.order.slice(start, end + 1))

                    return
                }
            }

            this.anchor = key
            this.$wire.select(key, event.ctrlKey || event.metaKey)
        },

        /** A search result opens in a window the same way a row does. */
        openFound(key, name, url, kind, meta) {
            window.dispatchEvent(new CustomEvent('open-document', {
                detail: { key, name, url, kind, meta, download: null, record: null },
            }))
        },

        /**
         * Opening a folder walks into it; opening a file goes to the file's
         * own page. The floating window is the second way to open something,
         * for reading it beside the list.
         */
        openEntry(key) {
            let entry = this.entry(key)

            if (! entry) return

            if (entry.type === 'folder' && entry.location) {
                this.$wire.open(entry.location)

                return
            }

            if (entry.view) window.location.href = `/files/${key}`
        },

        openFloating(key) {
            let entry = this.entry(key)

            if (! entry?.view) return

            window.dispatchEvent(new CustomEvent('open-document', {
                detail: {
                    key,
                    name: entry.name,
                    url: entry.view,
                    kind: entry.kind ?? 'other',
                    download: entry.download,
                    record: entry.record,
                    meta: entry.meta,
                },
            }))
        },

        showMenu(event, key) {
            let selected = this.$wire.selected ?? []

            if (! selected.includes(key)) {
                this.$wire.select(key, false)
                this.anchor = key
            }

            // Keep the menu on screen near the pointer.
            this.menu = {
                open: true,
                key,
                x: Math.min(event.clientX, window.innerWidth - 220),
                y: Math.min(event.clientY, window.innerHeight - 200),
            }
        },

        remove() {
            this.$wire.deleteSelected()
        },

        onKey(event) {
            // Never while something is being typed into.
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return

            let selected = this.$wire.selected ?? []

            if (event.key === 'F2' && this.canOrganise && selected.length === 1) {
                event.preventDefault()
                this.$wire.startRename(selected[0])
            } else if (event.key === 'Delete' && this.canOrganise && selected.length > 0) {
                event.preventDefault()

                if (window.confirm('Delete the selected items? Folders must be empty first.')) this.remove()
            } else if (event.key === 'Enter' && selected.length === 1) {
                event.preventDefault()
                this.openEntry(selected[0])
            } else if (event.key === 'Escape') {
                this.menu.open = false
            }
        },

        startDrag(event, key) {
            // Dragging an unselected entry drags just that one.
            let selected = this.$wire.selected ?? []
            this.dragged = selected.includes(key) ? [...selected] : [key]
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', key)
        },

        dropOnFolder(folderId) {
            this.dragFolder = null

            if (this.dragged.length === 0) return

            this.$wire.moveInto(folderId, this.dragged)
            this.dragged = []
        },

        // Files dragged in from outside the browser.
        dragOverWindow(event) {
            if ([...(event.dataTransfer?.types ?? [])].includes('Files')) this.dropping = true
        },

        dragLeaveWindow(event) {
            if (! event.relatedTarget) this.dropping = false
        },

        dropFiles(event) {
            this.dropping = false
            this.queueFiles([...(event.dataTransfer?.files ?? [])])
        },

        /*
         * Uploading, a piece at a time.
         *
         * The browser slices each file into 2 MB pieces and sends them one by
         * one. A stalled connection costs one piece rather than the whole
         * file, and no single request has to carry a large scan. The last call
         * assembles the pieces, stores the file and reads it for searching.
         */
        queueFiles(files) {
            this.uploadError = ''

            for (let file of files) {
                this.queued.push({
                    id: `${file.name}-${file.size}-${Math.random().toString(36).slice(2, 8)}`,
                    file,
                    name: file.name,
                    sizeLabel: this.sizeLabel(file.size),
                    percent: 0,
                    state: 'waiting',
                    note: '',
                })
            }
        },

        sizeLabel(bytes) {
            if (bytes < 1024) return `${bytes} B`
            if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`

            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
        },

        async sendFiles() {
            if (this.sending || this.queued.length === 0) return

            this.sending = true
            this.uploadError = ''
            window.dispatchEvent(new CustomEvent('dts-upload-start'))

            for (let entry of this.queued) {
                if (entry.state === 'done') continue

                try {
                    await this.sendOne(entry)
                } catch (error) {
                    entry.state = 'failed'
                    entry.note = 'failed'
                    this.uploadError = error.message ?? 'The upload did not finish.'
                }
            }

            this.sending = false
            window.dispatchEvent(new CustomEvent('dts-upload-finish'))

            // Keep anything that failed on screen to try again; clear the rest.
            this.queued = this.queued.filter((entry) => entry.state === 'failed')

            this.$wire.$refresh()
        },

        async sendOne(entry) {
            let size = {{ \App\Http\Controllers\ChunkedUploadController::CHUNK_BYTES }}
            let total = Math.max(1, Math.ceil(entry.file.size / size))
            let upload = (crypto.randomUUID?.() ?? `u${Date.now()}${Math.random().toString(36).slice(2)}`).replace(/[^A-Za-z0-9-]/g, '')

            entry.state = 'sending'

            for (let index = 0; index < total; index++) {
                let piece = entry.file.slice(index * size, (index + 1) * size)
                let body = new FormData()

                body.append('upload', upload)
                body.append('index', index)
                body.append('total', total)
                body.append('chunk', piece, `${index}`)

                let response = await this.post('{{ route('documents.upload.chunk') }}', body)

                if (! response.ok) throw new Error('A piece of the file did not arrive. Please try again.')

                entry.percent = Math.round(((index + 1) / total) * 100)
                entry.note = `${entry.percent}%`
                window.dispatchEvent(new CustomEvent('dts-upload-progress', { detail: { percent: entry.percent } }))
            }

            // Assembling, storing and reading happen in one last call.
            entry.note = 'reading…'

            let body = new FormData()
            body.append('upload', upload)
            body.append('name', entry.name)
            body.append('office', @js($office))
            if (@js($folder?->id)) body.append('folder', @js($folder?->id))
            if (this.uploadCategory) body.append('category', this.uploadCategory)

            let response = await this.post('{{ route('documents.upload.finish') }}', body)
            let result = await response.json().catch(() => ({}))

            if (! response.ok) throw new Error(result.message ?? 'The file could not be stored.')

            entry.state = 'done'
            entry.percent = 100
            entry.note = result.read?.status === 'done'
                ? `read by ${result.read.method}`
                : 'uploaded'
        },

        post(url, body) {
            return fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
            })
        },
    }))
</script>
@endscript
