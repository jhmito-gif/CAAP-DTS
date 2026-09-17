{{--
    Floating document viewer. Include once on a page; anything on that page can
    open a document in its own window:

        window.dispatchEvent(new CustomEvent('open-document', { detail: {...} }))

    Windows are dragged by the title bar, resized from the corner, minimised to
    the strip at the foot of the screen, and remember where they were put.
--}}
<div
    x-data="documentWindows()"
    @pointermove.window="onMove($event)"
    @pointerup.window="endMove()"
    {{-- Above the sticky navigation (z-50), or it would swallow clicks on a
         window that overlaps it. --}}
    class="pointer-events-none fixed inset-0 z-[60]"
    aria-live="polite"
>
    <template x-for="win in windows" :key="win.key">
        <div
            x-show="! win.minimised"
            x-cloak
            :style="`top: ${win.y}px; left: ${win.x}px; width: ${win.width}px; height: ${win.height}px; z-index: ${win.z}`"
            @pointerdown="focus(win)"
            class="pointer-events-auto absolute flex flex-col overflow-hidden border border-gray-300 bg-white shadow-2xl dark:border-gray-600 dark:!bg-gray-800"
            :class="win.dock === 'float' ? 'rounded-xl' : (win.dock === 'left' ? 'rounded-r-xl border-l-0' : 'rounded-l-xl border-r-0')"
            role="dialog"
            data-viewer-window
            :data-window="win.key"
            :aria-label="win.name"
        >
            {{-- Title bar --}}
            <div
                @pointerdown="startDrag($event, win)"
                @dblclick="maximise(win)"
                class="flex shrink-0 cursor-move items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/60"
            >
                <svg class="size-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75A3.375 3.375 0 0 0 11.25 3.375H6.75A2.25 2.25 0 0 0 4.5 5.625v12.75A2.25 2.25 0 0 0 6.75 20.625h10.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" /></svg>

                <p class="min-w-0 flex-1 truncate text-xs font-semibold text-gray-800 dark:text-gray-100" x-text="win.name"></p>

                <span class="hidden truncate text-[10px] text-gray-400 sm:block" x-text="win.meta"></span>

                <div class="flex shrink-0 items-center gap-0.5">
                    <button type="button" @pointerdown.stop @click="toggleInfo(win)"
                        :aria-label="`${win.showInfo ? 'Hide' : 'Show'} details of ${win.name}`"
                        :class="win.showInfo ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-400'"
                        class="rounded p-1 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    </button>

                    <button type="button" @pointerdown.stop @click="dockTo(win, 'left')"
                        :aria-label="`Dock ${win.name} to the left`"
                        :class="win.dock === 'left' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-400'"
                        class="rounded p-1 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h6v15h-6zM9.75 4.5h10.5v15H9.75z" /></svg>
                    </button>

                    <button type="button" @pointerdown.stop @click="dockTo(win, 'right')"
                        :aria-label="`Dock ${win.name} to the right`"
                        :class="win.dock === 'right' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-400'"
                        class="rounded p-1 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 4.5h6v15h-6zM3.75 4.5h10.5v15H3.75z" /></svg>
                    </button>

                    <button type="button" @pointerdown.stop @click="minimise(win)" :aria-label="`Minimise ${win.name}`"
                        class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" d="M5 12h14" /></svg>
                    </button>
                    <button type="button" @pointerdown.stop @click="maximise(win)" :aria-label="`Maximise ${win.name}`"
                        class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4z" /></svg>
                    </button>
                    <button type="button" @pointerdown.stop @click="close(win)" :aria-label="`Close ${win.name}`"
                        class="rounded p-1 text-gray-400 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-900/40">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>
            </div>

            {{-- Body: the document, with its details beside it --}}
            <div class="flex min-h-0 flex-1">
            <div class="min-h-0 flex-1 overflow-auto bg-gray-100 p-4 text-center dark:bg-gray-900">
                <template x-if="win.loading">
                    <p class="py-16 text-xs text-gray-500">Opening…</p>
                </template>

                <template x-if="win.error">
                    <div class="py-16">
                        <p class="text-xs font-semibold text-rose-600" x-text="win.error"></p>
                        <template x-if="win.download">
                            <a :href="win.download" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:underline">Download it instead</a>
                        </template>
                    </div>
                </template>

                <template x-if="win.kind === 'pdf' && ! win.error">
                    <canvas :data-canvas="win.key" class="mx-auto block bg-white shadow"></canvas>
                </template>

                <template x-if="win.kind === 'image'">
                    <img :src="win.url" :alt="win.name" class="mx-auto block max-w-full bg-white shadow">
                </template>

                <template x-if="win.kind === 'other'">
                    <div class="py-16">
                        <p class="text-xs text-gray-500">This kind of file cannot be shown here.</p>
                        <template x-if="win.download">
                            <a :href="win.download" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:underline">Download it</a>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Details --}}
            <aside
                x-show="win.showInfo"
                x-cloak
                class="w-72 shrink-0 overflow-auto border-l border-gray-200 bg-white px-3 py-3 text-left dark:border-gray-700 dark:!bg-gray-800"
                aria-label="Document details"
            >
                <template x-if="win.infoError">
                    <p class="text-[11px] font-semibold text-rose-600" x-text="win.infoError"></p>
                </template>

                <template x-if="! win.info && ! win.infoError">
                    <p class="text-[11px] text-gray-400">Looking it up…</p>
                </template>

                <template x-if="win.info">
                    <div class="space-y-3">
                        <div>
                            <p class="break-words text-xs font-bold text-gray-900 dark:text-gray-100" x-text="win.info.name"></p>
                            <p class="text-[10px] uppercase tracking-wide text-gray-400" x-text="win.info.kind"></p>
                        </div>

                        <dl class="space-y-1.5">
                            <template x-for="fact in win.info.facts" :key="fact.label">
                                <div class="grid grid-cols-3 gap-2">
                                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-gray-400" x-text="fact.label"></dt>
                                    <dd class="col-span-2 break-words text-[11px] text-gray-700 dark:text-gray-200" x-text="fact.value || '—'"></dd>
                                </div>
                            </template>
                        </dl>

                        {{-- Signatures --}}
                        <div x-show="win.info.signatures.length > 0 || win.info.awaiting_signatures > 0" class="border-t border-gray-100 pt-2 dark:border-gray-700">
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">Signatures</p>

                            <template x-for="signature in win.info.signatures" :key="signature.code">
                                <div class="mb-1.5 rounded-md bg-emerald-50 px-2 py-1.5 dark:bg-emerald-900/20">
                                    <p class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300" x-text="signature.signer"></p>
                                    <p class="text-[10px] text-emerald-700/80 dark:text-emerald-400/80" x-text="signature.office"></p>
                                    <p class="text-[10px] text-gray-500" x-text="signature.signed_at"></p>
                                    <p class="font-mono text-[10px] text-gray-500" x-text="signature.code"></p>
                                    <p x-show="signature.pages.length > 1" class="text-[10px] text-gray-500">
                                        Pages <span x-text="signature.pages.join(', ')"></span>
                                    </p>
                                </div>
                            </template>

                            <p x-show="win.info.awaiting_signatures > 0" class="text-[11px] text-amber-700 dark:text-amber-400">
                                <span x-text="win.info.awaiting_signatures"></span> still to sign
                            </p>
                        </div>

                        {{-- Searchability --}}
                        <div class="border-t border-gray-100 pt-2 dark:border-gray-700">
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">Searching</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-300" x-text="win.info.reading.summary"></p>
                        </div>

                        <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-2 text-[11px] font-semibold dark:border-gray-700">
                            <template x-if="win.info.record_url">
                                <a :href="win.info.record_url" class="text-indigo-600 hover:underline dark:text-indigo-400">Open the record</a>
                            </template>
                            <template x-if="win.info.download_url">
                                <a :href="win.info.download_url" class="text-indigo-600 hover:underline dark:text-indigo-400">Download</a>
                            </template>
                        </div>
                    </div>
                </template>
            </aside>
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center gap-1.5 border-t border-gray-200 bg-white px-3 py-1.5 text-[11px] dark:border-gray-700 dark:!bg-gray-800">
                <template x-if="win.kind === 'pdf' && win.pages > 0">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="go(win, -1)" :disabled="win.page <= 1" class="rounded border border-gray-200 px-1.5 py-0.5 font-semibold disabled:opacity-40 dark:border-gray-700">Prev</button>
                        <span class="text-gray-500">Page <span x-text="win.page"></span> of <span x-text="win.pages"></span></span>
                        <button type="button" @click="go(win, 1)" :disabled="win.page >= win.pages" class="rounded border border-gray-200 px-1.5 py-0.5 font-semibold disabled:opacity-40 dark:border-gray-700">Next</button>

                        <span class="mx-1 text-gray-300">|</span>

                        <button type="button" @click="zoom(win, 0.8)" aria-label="Zoom out" class="rounded border border-gray-200 px-1.5 py-0.5 font-semibold dark:border-gray-700">&minus;</button>
                        <button type="button" @click="fitToWindow(win)"
                            :class="win.fit ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700'"
                            class="rounded border px-1.5 py-0.5 font-semibold">Fit</button>
                        <button type="button" @click="zoom(win, 1.25)" aria-label="Zoom in" class="rounded border border-gray-200 px-1.5 py-0.5 font-semibold dark:border-gray-700">+</button>
                    </div>
                </template>

                <div class="ml-auto flex items-center gap-2">
                    {{-- The file's own page: an address that can be kept. --}}
                    <template x-if="/^(attachment|document|shortcut)-\d+$/.test(win.key)">
                        <a :href="`/files/${win.key}`" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Open as page</a>
                    </template>
                    <template x-if="win.record">
                        <a :href="win.record" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Record</a>
                    </template>
                    <template x-if="win.download">
                        <a :href="win.download" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Download</a>
                    </template>
                    <a :href="win.url" target="_blank" rel="noopener" class="font-semibold text-gray-500 hover:underline">New tab</a>
                </div>
            </div>

            {{-- Docked: the inner edge is dragged to make it wider or narrower --}}
            <div
                x-show="win.dock !== 'float'"
                x-cloak
                @pointerdown.prevent="startResize($event, win)"
                :class="win.dock === 'left' ? 'right-0' : 'left-0'"
                class="absolute inset-y-0 w-1.5 cursor-ew-resize bg-transparent transition hover:bg-indigo-400/40"
                :aria-label="`Resize ${win.name}`"
                role="separator"
            ></div>

            {{-- Floating: the corner grip --}}
            <div
                x-show="win.dock === 'float'"
                @pointerdown.prevent="startResize($event, win)"
                class="absolute bottom-0 right-0 size-4 cursor-se-resize"
                aria-hidden="true"
            >
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 16 16" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" d="M11 15 15 11M6 15l9-9" /></svg>
            </div>
        </div>
    </template>

    {{-- Minimised strip --}}
    <div class="pointer-events-auto fixed bottom-0 left-0 flex max-w-full flex-wrap gap-1.5 p-2" x-show="windows.some(w => w.minimised)" x-cloak>
        <template x-for="win in windows.filter(w => w.minimised)" :key="`min-${win.key}`">
            <button
                type="button"
                @click="win.minimised = false; focus(win); win.kind === 'pdf' && render(win)"
                class="inline-flex max-w-[220px] items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-gray-700 shadow-lg hover:border-indigo-300 hover:text-indigo-700 dark:border-gray-600 dark:!bg-gray-800 dark:text-gray-200"
            >
                <svg class="size-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75A3.375 3.375 0 0 0 11.25 3.375H6.75A2.25 2.25 0 0 0 4.5 5.625v12.75A2.25 2.25 0 0 0 6.75 20.625h10.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" /></svg>
                <span class="truncate" x-text="win.name"></span>
            </button>
        </template>
    </div>
</div>
