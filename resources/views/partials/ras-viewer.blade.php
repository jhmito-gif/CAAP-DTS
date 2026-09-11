@php
    use Illuminate\Support\Js;

    $rasTransactions = collect($rasTransactions ?? $record->transactions)->values();
    $latestTransaction = $rasTransactions->sortByDesc('created_at')->first();
    $viewerRevision = $rasTransactions
        ->map(fn ($transaction) => optional($transaction->updated_at)->timestamp ?? 0)
        ->max() ?: optional($record->updated_at)->timestamp;

    $rasUrl = route('records-pdf', $record->id)
        . '?revision=' . $viewerRevision
        . '#page=1&zoom=page-width&view=FitH&toolbar=0&navpanes=0';

    // Confidential files are only listed for cleared viewers.
    $canSeeFiles = ! $record->isMaskedFor(auth()->user());

    $viewableAttachments = $canSeeFiles
        ? $record->attachments->filter(fn ($a) => $a->is_pdf || $a->is_image)->values()
        : collect();

    // File list for the switcher: RAS first, then viewable files.
    $files = collect([[
        'key' => 'ras',
        'label' => 'Routing Action Slip',
        'url' => $rasUrl,
    ]]);

    foreach ($viewableAttachments as $a) {
        $files->push([
            'key' => 'att-' . $a->id,
            'label' => $a->original_name,
            'url' => route('attachments.view', $a) . '#zoom=page-width&view=FitH&toolbar=0',
        ]);
    }
@endphp

<section
    class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800"
    aria-labelledby="ras-viewer-title"
    x-data="{
        active: 'ras',
        src: {{ Js::from($rasUrl) }},
        select(key, url) { this.active = key; this.src = url; }
    }"
>
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75A3.375 3.375 0 0 0 11.25 3.375H6.75A2.25 2.25 0 0 0 4.5 5.625v12.75A2.25 2.25 0 0 0 6.75 20.625h10.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.625 3.75V8.25h4.5" />
                </svg>
            </div>

            <div class="min-w-0">
                <h2 id="ras-viewer-title" class="truncate text-sm font-bold text-gray-900 dark:text-gray-100">Document Viewer</h2>
                <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                    RAS &amp; attached files
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    {{ $files->count() }} {{ \Illuminate\Support\Str::plural('document', $files->count()) }}
                </p>
            </div>
        </div>

        <a
            :href="src"
            target="_blank"
            rel="noopener"
            title="Open current document in a new tab"
            class="inline-flex shrink-0 items-center gap-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 transition hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5M10.5 13.5 21 3m0 0h-6.75M21 3v6.75" />
            </svg>
            Open
        </a>
    </div>

    {{-- File switcher: RAS + attachments --}}
    <div class="flex items-center gap-1.5 overflow-x-auto border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2">
        @foreach ($files as $file)
            <button
                type="button"
                @click="select({{ Js::from($file['key']) }}, {{ Js::from($file['url']) }})"
                :class="active === {{ Js::from($file['key']) }}
                    ? 'border-blue-500 bg-blue-600 text-white'
                    : 'border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-blue-300 dark:hover:border-blue-700 hover:text-blue-700 dark:hover:text-blue-300'"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-[11px] font-semibold transition"
            >
                @if ($file['key'] === 'ras')
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75A3.375 3.375 0 0 0 11.25 3.375H6.75A2.25 2.25 0 0 0 4.5 5.625v12.75A2.25 2.25 0 0 0 6.75 20.625h10.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" />
                    </svg>
                @else
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                    </svg>
                @endif
                <span class="max-w-[160px] truncate">{{ $file['label'] }}</span>
            </button>
        @endforeach

        @if (! $canSeeFiles && $record->is_confidential)
            <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/30 px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                Files hidden (confidential)
            </span>
        @endif
    </div>

    <div class="flex items-center justify-between gap-3 bg-gray-900 px-4 py-2 text-[11px]">
        <div class="flex items-center gap-2 text-gray-300">
            <span class="size-1.5 rounded-full bg-emerald-400"></span>
            <span>Live preview</span>
        </div>

        <span class="max-w-[220px] truncate font-semibold text-amber-300">
            {{ $latestTransaction?->status ?: 'Not routed' }}
        </span>
    </div>

    <div class="{{ $heightClass ?? 'h-[720px]' }} bg-gray-200 dark:bg-gray-700 p-2">
        <iframe
            wire:ignore
            :src="src"
            title="Document viewer for {{ $record->reference }}"
            class="block size-full border-0 bg-white dark:!bg-gray-800"
        ></iframe>
    </div>
</section>
