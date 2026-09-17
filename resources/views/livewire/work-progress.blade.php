{{--
    The floating progress bar. Sits in the app layout, so it shows the same
    thing on every page: work tracked in the database (reading documents)
    carries across navigation, and uploads on this page join it while they run.
--}}
<div
    wire:poll.{{ $interval }}
    x-data="workProgress()"
    {{-- x-on:, not @: Blade would read "@livewire-upload-start" as its own
         @livewire directive and try to render a component. --}}
    x-on:livewire-upload-start.window="startUpload()"
    x-on:livewire-upload-progress.window="uploadPercent = $event.detail.progress"
    x-on:livewire-upload-finish.window="endUpload()"
    x-on:livewire-upload-error.window="endUpload()"
    {{-- The chunked uploader reports the same way. --}}
    x-on:dts-upload-start.window="startUpload()"
    x-on:dts-upload-progress.window="uploadPercent = $event.detail.percent"
    x-on:dts-upload-finish.window="endUpload()"
    class="pointer-events-none fixed bottom-4 right-4 z-[80] w-72 max-w-[calc(100vw-2rem)]"
    aria-live="polite"
>
    {{-- Reading documents: carries on across pages --}}
    @if ($reading['show'])
        <div class="pointer-events-auto mb-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:!bg-gray-800"
             x-show="! collapsed" x-transition.opacity>
            <div class="flex items-start gap-2.5 px-3 py-2.5">
                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                    @if ($reading['finished'])
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" /></svg>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">
                        {{ $reading['finished'] ? 'Documents read' : 'Reading documents' }}
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $reading['done'] }} of {{ $reading['total'] }} done
                        @if ($reading['failed'])
                            &middot; <span class="text-rose-600 dark:text-rose-400">{{ $reading['failed'] }} could not be read</span>
                        @endif
                    </p>
                </div>

                <button type="button" @click="collapsed = true" aria-label="Hide progress"
                    class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" d="M19.5 8.25 12 15.75l-7.5-7.5" /></svg>
                </button>
            </div>

            <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700" role="progressbar"
                 aria-valuenow="{{ $reading['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="Reading documents">
                <div class="h-full rounded-r-full bg-indigo-600 transition-all duration-500 dark:bg-indigo-500"
                     style="width: {{ max($reading['percent'], $reading['finished'] ? 100 : 4) }}%"></div>
            </div>
        </div>

        {{-- Collapsed to a pill, still showing how far along it is --}}
        <button
            type="button"
            x-show="collapsed"
            x-cloak
            @click="collapsed = false"
            class="pointer-events-auto mb-2 ml-auto flex items-center gap-2 rounded-full border border-gray-200 bg-white py-1.5 pl-2.5 pr-3 text-[11px] font-semibold text-gray-700 shadow-lg dark:border-gray-700 dark:!bg-gray-800 dark:text-gray-200"
        >
            <span class="relative flex size-4 items-center justify-center">
                <svg class="size-4 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-opacity="0.15" stroke-width="6" />
                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"
                        stroke-dasharray="{{ round($reading['percent'] * 0.94, 1) }} 94" class="text-indigo-600 dark:text-indigo-400" />
                </svg>
            </span>
            {{ $reading['percent'] }}%
        </button>
    @endif

    {{-- Uploads happening on this page --}}
    <div
        x-show="uploading"
        x-cloak
        x-transition.opacity
        class="pointer-events-auto overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:!bg-gray-800"
    >
        <div class="flex items-center gap-2.5 px-3 py-2.5">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">Uploading</p>
                <p class="truncate text-[11px] text-gray-500 dark:text-gray-400"><span x-text="uploadPercent"></span>% sent</p>
            </div>
        </div>
        <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700" role="progressbar" :aria-valuenow="uploadPercent" aria-valuemin="0" aria-valuemax="100" aria-label="Uploading">
            <div class="h-full rounded-r-full bg-emerald-600 transition-all duration-200" :style="`width: ${Math.max(uploadPercent, 4)}%`"></div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('workProgress', () => ({
        // Collapsing is remembered for this browser, so it stays collapsed
        // as you move between pages.
        collapsed: (() => {
            try { return localStorage.getItem('dts.progress.collapsed') === '1' } catch { return false }
        })(),

        uploading: false,
        uploadPercent: 0,

        init() {
            this.$watch('collapsed', (value) => {
                try { localStorage.setItem('dts.progress.collapsed', value ? '1' : '0') } catch { /* private window */ }
            })
        },

        startUpload() {
            this.uploadPercent = 0
            this.uploading = true
        },

        endUpload() {
            this.uploadPercent = 100
            setTimeout(() => { this.uploading = false }, 600)
        },
    }))
</script>
@endscript
