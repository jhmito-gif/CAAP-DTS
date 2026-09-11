@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Facades\URL;

    $rasTransactions = collect($rasTransactions ?? $record->transactions)->values();
    $latestTransaction = $rasTransactions->sortByDesc('created_at')->first();
    $viewerRevision = $rasTransactions
        ->map(fn ($transaction) => optional($transaction->updated_at)->timestamp ?? 0)
        ->max() ?: optional($record->updated_at)->timestamp;

    // Confidential files/RAS are only for cleared viewers.
    $canSeeFiles = ! $record->isMaskedFor(auth()->user());

    // Token gate: a token-protected record stays locked until the viewer
    // enters the token (tracked by the parent component's $confidentialUnlocked).
    $tokenRequired = $record->requiresToken();
    $unlocked = ! $tokenRequired || ($confidentialUnlocked ?? false);

    // The RAS: a signed short-lived link (full slip) once unlocked; otherwise
    // the plain link, which the controller serves redacted/watermarked.
    $rasFragment = '#page=1&zoom=page-width&view=FitH&toolbar=0&navpanes=0';
    $rasUrl = ($tokenRequired && $unlocked)
        ? URL::temporarySignedRoute('records-pdf', now()->addMinutes(5), ['id' => $record->id, 'revision' => $viewerRevision]) . $rasFragment
        : route('records-pdf', $record->id) . '?revision=' . $viewerRevision . $rasFragment;

    // All attachments are listed when cleared AND unlocked. PDFs/images preview
    // inline; other types (Word, Excel, ...) can't render in a frame, so they
    // offer a download instead. Signed URLs when token-gated.
    $attachmentsToShow = ($canSeeFiles && $unlocked)
        ? $record->attachments->values()
        : collect();

    $viewUrl = fn ($a) => $tokenRequired
        ? URL::temporarySignedRoute('attachments.view', now()->addMinutes(5), ['attachment' => $a->id]) . '#zoom=page-width&view=FitH&toolbar=0'
        : route('attachments.view', $a) . '#zoom=page-width&view=FitH&toolbar=0';

    $downloadUrl = fn ($a) => $tokenRequired
        ? URL::temporarySignedRoute('attachments.download', now()->addMinutes(5), ['attachment' => $a->id])
        : route('attachments.download', $a);

    // File list for the switcher: RAS first, then every attachment.
    // Confidential files are view-only -- no download link is ever emitted.
    $files = collect([[
        'key' => 'ras',
        'label' => 'Routing Action Slip',
        'ext' => 'RAS',
        'previewable' => true,
        'view' => $rasUrl,
        'download' => '',
    ]]);

    foreach ($attachmentsToShow as $a) {
        $previewable = $a->is_pdf || $a->is_image;
        $confidential = $a->isConfidential();
        $files->push([
            'key' => 'att-' . $a->id,
            'label' => $a->original_name,
            'ext' => $a->extension,
            'previewable' => $previewable,
            'view' => $previewable ? $viewUrl($a) : '',
            'download' => $confidential ? '' : $downloadUrl($a),
        ]);
    }

    $showUnlock = $canSeeFiles && $tokenRequired && ! $unlocked;
@endphp

<section
    class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800"
    aria-labelledby="ras-viewer-title"
    x-data="{
        active: 'ras',
        label: 'Routing Action Slip',
        ext: 'RAS',
        previewable: true,
        view: {{ Js::from($rasUrl) }},
        download: '',
        get src() { return this.view; },
        select(f) { this.active = f.key; this.label = f.label; this.ext = f.ext; this.previewable = f.previewable; this.view = f.view; this.download = f.download; }
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
            x-show="view || download"
            :href="view || download"
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
                @click="select({{ Js::from($file) }})"
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
        @if ($showUnlock)
            {{-- Token gate: cleared viewer must enter the access token to reveal the slip and files. --}}
            <div class="flex size-full flex-col items-center justify-center gap-4 rounded bg-white px-6 text-center dark:!bg-gray-800">
                <div class="flex size-14 items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Confidential &mdash; access token required</p>
                    <p class="mt-1 max-w-xs text-xs text-gray-500 dark:text-gray-400">
                        Enter the record's access token to view the routing slip and its files. Required each time you open this record.
                    </p>
                </div>

                <form wire:submit.prevent="unlockConfidential" class="flex w-full max-w-xs flex-col gap-2">
                    <input
                        type="password"
                        wire:model="unlockToken"
                        autocomplete="off"
                        placeholder="Access token"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-900 px-3 py-2 text-center text-sm text-gray-800 dark:text-gray-100 shadow-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                    >

                    @error('unlockToken')
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        Unlock
                    </button>
                </form>

                <button
                    type="button"
                    x-data
                    @click="$wire.dispatch('request-access-token', { recordId: {{ $record->id }} })"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:underline"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    Don&rsquo;t have the token? Request it in chat
                </button>
            </div>
        @else
            {{-- PDFs / images preview inline --}}
            <iframe
                x-show="previewable"
                wire:ignore
                :src="src"
                title="Document viewer for {{ $record->reference }}"
                class="block size-full border-0 bg-white dark:!bg-gray-800"
            ></iframe>

            {{-- Non-previewable + downloadable (Word, Excel, ... on non-confidential records) --}}
            <div
                x-show="! previewable && download"
                x-cloak
                class="flex size-full flex-col items-center justify-center gap-4 rounded bg-white px-6 text-center dark:!bg-gray-800"
            >
                <div class="flex size-16 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <span class="text-sm font-extrabold" x-text="ext"></span>
                </div>

                <div>
                    <p class="max-w-xs truncate text-sm font-bold text-gray-900 dark:text-gray-100" x-text="label"></p>
                    <p class="mt-1 max-w-xs text-xs text-gray-500 dark:text-gray-400">
                        This file type can&rsquo;t be previewed here. Download it to open in the right app.
                    </p>
                </div>

                <a
                    :href="download"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download
                </a>
            </div>

            {{-- Confidential + non-previewable: view-only, no download possible --}}
            <div
                x-show="! previewable && ! download"
                x-cloak
                class="flex size-full flex-col items-center justify-center gap-4 rounded bg-white px-6 text-center dark:!bg-gray-800"
            >
                <div class="flex size-16 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>

                <div>
                    <p class="max-w-xs truncate text-sm font-bold text-gray-900 dark:text-gray-100" x-text="label"></p>
                    <p class="mt-1 max-w-xs text-xs text-gray-500 dark:text-gray-400">
                        Confidential file &mdash; view-only. This type can&rsquo;t be previewed in the browser and downloading is disabled.
                    </p>
                </div>
            </div>
        @endif
    </div>
</section>
