@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20';
    $filter = 'rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 py-2 pl-3 pr-8 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20';
    $error = 'mt-1.5 text-xs font-medium text-red-600 dark:text-red-400';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200';
    $iconButton = 'inline-flex size-8 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-500 dark:text-gray-400 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-300';
@endphp

<div class="w-full min-h-screen bg-gray-50/60 dark:bg-gray-900 pt-6 pb-10">
    <div class="mx-auto max-w-full px-4 lg:px-8">

        {{-- Toolbar --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-document-upload')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-indigo-700"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    Upload
                </button>

                <span class="hidden text-sm text-gray-400 dark:text-gray-500 sm:inline">
                    {{ $rows->total() }} {{ Str::plural('file', $rows->total()) }}
                </span>
            </div>

            <div class="relative w-full sm:w-96">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    aria-label="Search documents"
                    placeholder="Search file name, reference, subject…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 py-2 pl-9 pr-3 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                >
            </div>
        </div>

        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <select wire:model.live="office" aria-label="Office" class="{{ $filter }}">
                <option value="">All offices</option>
                @foreach ($offices as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <select wire:model.live="category" aria-label="Category" class="{{ $filter }}">
                <option value="">All categories</option>
                <option value="none">Uncategorised</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <select wire:model.live="type" aria-label="File type" class="{{ $filter }}">
                <option value="">All file types</option>
                @foreach ($types as $value => $name)
                    <option value="{{ $value }}">{{ $name }}</option>
                @endforeach
            </select>

            <select wire:model.live="source" aria-label="Source" class="{{ $filter }}">
                <option value="">Routed &amp; library</option>
                @foreach ($sources as $value => $name)
                    <option value="{{ $value }}">{{ $name }}</option>
                @endforeach
            </select>

            <select wire:model.live="signed" aria-label="Signature status" class="{{ $filter }}">
                <option value="">Any signature status</option>
                <option value="signed">Signed</option>
                <option value="unsigned">Not signed</option>
            </select>

            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <input type="date" wire:model.live="from" aria-label="Uploaded from" class="{{ $filter }} pr-3">
                <span>to</span>
                <input type="date" wire:model.live="to" aria-label="Uploaded to" class="{{ $filter }} pr-3">
            </div>

            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="px-2 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                    Clear filters
                </button>
            @endif
        </div>

        {{-- Table card --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm">
            <div wire:loading.delay class="absolute inset-x-0 top-0 h-0.5 animate-pulse bg-indigo-500"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
                    <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/95 dark:bg-gray-900 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Office</th>
                            <th class="px-4 py-3">Record</th>
                            <th class="px-4 py-3">Uploaded</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($rows as $item)
                            <tr wire:key="library-{{ $item->key }}" class="align-top transition-colors duration-100 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/20">

                                {{-- File --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <span @class([
                                            'flex h-9 w-11 shrink-0 items-center justify-center rounded-md text-[10px] font-bold',
                                            'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300' => $item->kind === 'pdf',
                                            'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300' => $item->kind === 'image',
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $item->kind === 'other',
                                        ])>
                                            {{ Str::limit($item->extension, 4, '') }}
                                        </span>

                                        <div class="min-w-0">
                                            <p class="max-w-[320px] truncate font-semibold text-gray-800 dark:text-gray-100" title="{{ $item->title }}">
                                                {{ $item->title }}
                                            </p>
                                            <p class="max-w-[320px] truncate text-xs text-gray-400 dark:text-gray-500">
                                                @if ($item->title !== $item->original_name)
                                                    {{ $item->original_name }} &middot;
                                                @endif
                                                {{ $item->size }}
                                            </p>

                                            @if ($item->description)
                                                <p class="mt-0.5 max-w-[320px] truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $item->description }}">
                                                    {{ $item->description }}
                                                </p>
                                            @endif

                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @if ($item->source === 'document')
                                                    <span class="rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:text-indigo-300">Library</span>
                                                @endif
                                                @if ($item->signed)
                                                    <span class="rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">Signed</span>
                                                @endif
                                                @if ($item->confidential)
                                                    <span class="rounded-full bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:text-rose-300">Confidential</span>
                                                @endif
                                                @if ($item->token_required)
                                                    <span class="rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-300">Access token</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-4 py-3">
                                    @if ($item->category)
                                        <span class="inline-block rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-200">{{ $item->category }}</span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                                    @endif
                                </td>

                                {{-- Office --}}
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $item->office ?? '—' }}
                                </td>

                                {{-- Record --}}
                                <td class="px-4 py-3">
                                    @if ($item->reference)
                                        <a href="{{ $item->record_url }}" class="whitespace-nowrap font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $item->reference }}
                                        </a>
                                        <p class="max-w-[260px] truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $item->subject }}">
                                            {{ $item->subject }}
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Library upload</span>
                                    @endif
                                </td>

                                {{-- Uploaded --}}
                                <td class="px-4 py-3">
                                    <p class="max-w-[160px] truncate text-gray-600 dark:text-gray-300" title="{{ $item->uploaded_by }}">
                                        {{ $item->uploaded_by ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500" title="{{ $item->uploaded_at->format('M d, Y g:i A') }}">
                                        {{ $item->uploaded_at->format('M d, Y') }}
                                    </p>
                                </td>

                                {{-- Actions --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if ($item->view_url)
                                            <button
                                                type="button"
                                                x-data
                                                @click="$dispatch('preview-document', @js(['url' => $item->view_url, 'title' => $item->title, 'kind' => $item->kind, 'download' => $item->download_url]))"
                                                title="Preview"
                                                aria-label="Preview {{ $item->title }}"
                                                class="{{ $iconButton }}"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>
                                        @elseif ($item->record_url)
                                            <a href="{{ $item->record_url }}" title="Open the record and enter its access token" aria-label="Open record for {{ $item->title }}" class="{{ $iconButton }}">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                            </a>
                                        @endif

                                        @if ($item->download_url)
                                            <a href="{{ $item->download_url }}" title="Download" aria-label="Download {{ $item->title }}" class="{{ $iconButton }}">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                            </a>
                                        @endif

                                        @if ($item->can_manage)
                                            <button
                                                type="button"
                                                wire:click="editItem('{{ $item->source }}', {{ $item->id }})"
                                                title="{{ $item->source === 'document' ? 'Edit details' : 'Set category' }}"
                                                aria-label="{{ $item->source === 'document' ? 'Edit details of' : 'Set category of' }} {{ $item->title }}"
                                                class="{{ $iconButton }}"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                                </svg>
                                            </button>

                                            @if ($item->source === 'document')
                                                <button
                                                    type="button"
                                                    wire:click="deleteDocument({{ $item->id }})"
                                                    wire:confirm="Delete “{{ $item->title }}” from the library? This cannot be undone."
                                                    title="Delete"
                                                    aria-label="Delete {{ $item->title }}"
                                                    class="{{ $iconButton }} hover:!border-red-300 hover:!bg-red-50 hover:!text-red-600"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-20 text-center">
                                    <svg class="mx-auto mb-2 size-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                    </svg>
                                    <p class="text-sm text-gray-400 dark:text-gray-500">
                                        {{ $hasFilters ? 'No files match these filters' : 'No files yet' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination footer --}}
            <div class="flex flex-col gap-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <p class="order-3 text-xs text-gray-400 dark:text-gray-500 sm:order-1">
                    Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}
                </p>

                <div class="order-1 flex items-center gap-2 sm:order-2">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($rows->onFirstPage())
                        aria-label="Previous page"
                        class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <span class="px-1 text-xs text-gray-500 dark:text-gray-400">
                        Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}
                    </span>

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(! $rows->hasMorePages())
                        aria-label="Next page"
                        class="inline-flex size-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <div class="order-2 flex items-center gap-2 sm:order-3">
                    <label for="libraryPerPage" class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">Per page</label>
                    <select
                        id="libraryPerPage"
                        wire:model.live="perPage"
                        class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 py-1.5 pl-2 pr-6 text-xs text-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>


    {{-- ================================================================= --}}
    {{-- Upload modal --}}
    {{-- ================================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-document-upload.window="open = true"
        x-on:close-document-upload.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="documentUploadTitle"
    >
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>

        <form wire:submit="saveUploads" @click.stop class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 id="documentUploadTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Upload to the {{ auth()->user()->office }} library
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Stored encrypted. Visible to your office and administrators.
                </p>
            </div>

            <div class="space-y-4 overflow-y-auto px-6 py-5">
                <div>
                    <label for="libraryUploads" class="{{ $label }}">Files <span class="text-red-500">*</span></label>
                    <input
                        id="libraryUploads"
                        type="file"
                        multiple
                        wire:model="uploads"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white"
                    >
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">PDF, Word, Excel, JPG or PNG. Up to 10 files, 10 MB each.</p>
                    <p wire:loading wire:target="uploads" class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">Uploading…</p>

                    @error('uploads') <p class="{{ $error }}">{{ $message }}</p> @enderror

                    @if (count($uploads) > 0)
                        <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                            @foreach ($uploads as $index => $file)
                                <li wire:key="pending-upload-{{ $index }}" @class([
                                    'px-3 py-2 text-xs text-gray-700 dark:text-gray-200',
                                    'bg-red-50/60 dark:bg-red-900/20' => $errors->has("uploads.{$index}"),
                                ])>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="discardUpload({{ $index }})" aria-label="Remove {{ $file->getClientOriginalName() }}" class="text-gray-400 hover:text-red-600">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Each problem is shown against its own file. --}}
                                    @error("uploads.{$index}")
                                        <p class="mt-0.5 font-medium text-red-600 dark:text-red-400">{{ $message }} Remove it to continue.</p>
                                    @enderror
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if (count($uploads) <= 1)
                    <div>
                        <label for="libraryUploadTitle" class="{{ $label }}">Title</label>
                        <input id="libraryUploadTitle" type="text" wire:model="uploadTitle" placeholder="Defaults to the file name" class="{{ $input }}">
                        @error('uploadTitle') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="libraryUploadCategory" class="{{ $label }}">Category</label>
                    <select id="libraryUploadCategory" wire:model="uploadCategory" class="{{ $input }}">
                        <option value="">No category</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('uploadCategory') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="libraryUploadDescription" class="{{ $label }}">Description</label>
                    <textarea id="libraryUploadDescription" wire:model="uploadDescription" rows="3" class="{{ $input }} resize-none"></textarea>
                    @error('uploadDescription') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveUploads,uploads" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveUploads">Upload</span>
                    <span wire:loading wire:target="saveUploads">Saving…</span>
                </button>
            </div>
        </form>
    </div>


    {{-- ================================================================= --}}
    {{-- Edit modal --}}
    {{-- ================================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-document-edit.window="open = true"
        x-on:close-document-edit.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="documentEditTitle"
    >
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>

        <form wire:submit="saveEdit" @click.stop class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 id="documentEditTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editingSource === 'document' ? 'Edit document' : 'Set category' }}
                </h3>
                @if ($editingSource === 'attachment')
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $editTitle }}</p>
                @endif
            </div>

            <div class="space-y-4 px-6 py-5">
                @if ($editingSource === 'document')
                    <div>
                        <label for="libraryEditTitle" class="{{ $label }}">Title <span class="text-red-500">*</span></label>
                        <input id="libraryEditTitle" type="text" wire:model="editTitle" class="{{ $input }}">
                        @error('editTitle') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="libraryEditCategory" class="{{ $label }}">Category</label>
                    <select id="libraryEditCategory" wire:model="editCategory" class="{{ $input }}">
                        <option value="">No category</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('editCategory') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                @if ($editingSource === 'document')
                    <div>
                        <label for="libraryEditDescription" class="{{ $label }}">Description</label>
                        <textarea id="libraryEditDescription" wire:model="editDescription" rows="3" class="{{ $input }} resize-none"></textarea>
                        @error('editDescription') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveEdit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                    Save
                </button>
            </div>
        </form>
    </div>


    {{-- ================================================================= --}}
    {{-- Preview modal (Alpine only) --}}
    {{-- ================================================================= --}}
    <div
        wire:ignore
        x-data="{ open: false, url: '', title: '', kind: 'other', download: null }"
        x-on:preview-document.window="url = $event.detail.url; title = $event.detail.title; kind = $event.detail.kind; download = $event.detail.download; open = true"
        x-on:keydown.escape.window="open = false; url = ''"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Document preview"
    >
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="open = false; url = ''"></div>

        <div class="relative flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="title"></p>

                <div class="flex shrink-0 items-center gap-2">
                    <a x-show="download" :href="download" class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Download
                    </a>
                    <a :href="url" target="_blank" rel="noopener" class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Open in new tab
                    </a>
                    <button type="button" @click="open = false; url = ''" aria-label="Close preview" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 bg-gray-100 dark:bg-gray-900">
                <template x-if="open && kind === 'pdf'">
                    <iframe :src="url" class="h-full w-full" title="Document preview"></iframe>
                </template>

                <template x-if="open && kind === 'image'">
                    <div class="flex h-full items-center justify-center p-4">
                        <img :src="url" :alt="title" class="max-h-full max-w-full object-contain">
                    </div>
                </template>

                <template x-if="open && kind === 'other'">
                    <div class="flex h-full flex-col items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>This file type can't be previewed here.</p>
                        <a x-show="download" :href="download" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Download it instead</a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
