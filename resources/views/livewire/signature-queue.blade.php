@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20';
    $error = 'mt-1.5 text-xs font-medium text-red-600 dark:text-red-400';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200';
@endphp

<div class="w-full min-h-screen bg-gray-50/60 dark:bg-gray-900 pt-6 pb-10">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">

        {{-- Cannot sign at all yet --}}
        @if ($blocker)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">You can't sign yet</p>
                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">{{ $blocker }}</p>
                </div>
                <a href="{{ route('profile.show') }}" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">
                    Open your profile
                </a>
            </div>
        @endif

        {{-- Signing session --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-5 py-4 {{ $sessionOpen ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800' }}">

            <div class="min-w-0">
                @if ($sessionOpen)
                    <p class="flex items-center gap-2 text-sm font-bold text-emerald-800 dark:text-emerald-300">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Signing session open
                    </p>
                    <p class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400">
                        One click per document &middot; {{ $remaining }} of {{ $maxDocuments }} left
                        @if ($expiresAt)
                            &middot; until {{ $expiresAt->format('g:i A') }} unless you keep signing
                        @endif
                    </p>
                @else
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Signing session closed</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Authorise once, then sign each document with a single click.
                        @if ($deviceRemembered)
                            This device is remembered, so only your PIN is needed.
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if ($sessionOpen)
                    <button type="button" wire:click="endSession" wire:loading.attr="disabled" wire:target="endSession"
                        class="rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:!bg-gray-800 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white">
                        End signing session
                    </button>
                @elseif (! $blocker)
                    <button type="button" x-data @click="$dispatch('open-signing-session-modal')"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-sky-700">
                        Start signing session
                    </button>
                @endif
            </div>
        </div>

        {{-- Queue --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm">

            <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 px-5 py-4">
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Awaiting your signature</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        The page shown is where the signature will go
                    </p>
                </div>
                <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ $documents->total() }}
                </span>
            </div>

            @forelse ($documents as $document)
                <div wire:key="queue-{{ $document->id }}" class="flex flex-wrap items-start gap-4 border-b border-gray-100 dark:border-gray-800 px-5 py-4 last:border-b-0">

                    {{-- Page preview --}}
                    <div
                        wire:ignore
                        x-data="signatureThumb({ url: @js($document->url), page: @js($document->spot['page'] ?? 1), box: @js($document->spot ? ['x' => $document->spot['x'], 'y' => $document->spot['y'], 'width' => $document->spot['width'], 'height' => $document->spot['height']] : null) })"
                        class="relative w-[100px] shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
                    >
                        <canvas x-ref="canvas" class="block w-full"></canvas>
                        <p x-show="loading" class="py-8 text-center text-[10px] text-gray-400">Loading…</p>
                        <p x-show="failed" x-cloak class="py-8 text-center text-[10px] text-gray-400">No preview</p>
                    </div>

                    {{-- Details --}}
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $document->name }}</p>

                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                            <a href="{{ $document->record_url }}" class="font-semibold text-sky-600 hover:underline dark:text-sky-400">{{ $document->reference }}</a>
                            &middot; {{ $document->subject }}
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            @if ($document->spot)
                                <span class="rounded-full bg-sky-50 dark:bg-sky-900/30 px-2 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-300">
                                    @if (count($document->pages) > 1)
                                        Pages {{ implode(', ', $document->pages) }}
                                    @else
                                        Page {{ $document->spot['page'] }}
                                    @endif
                                </span>
                                <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                    @switch($document->spot['reason'])
                                        @case('marked by the sender') marked by {{ $document->spot['anchor'] ?: 'the sending office' }} @break
                                        @case('printed name') found your printed name @break
                                        @case('signature label') found "{{ \Illuminate\Support\Str::limit($document->spot['anchor'], 24) }}" @break
                                        @case('printed-name line') found the signature line @break
                                        @default no marker found — placed at the foot of the last page
                                    @endswitch
                                </span>
                            @else
                                <span class="rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-300">
                                    Placement unknown — open to place it
                                </span>
                            @endif

                            @if ($document->locked)
                                <span class="rounded-full bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:text-rose-300">
                                    Access token required
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ $document->sign_url }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Open
                        </a>

                        @if ($sessionOpen && $document->spot && ! $document->locked)
                            <button
                                type="button"
                                wire:click="sign({{ $document->id }})"
                                wire:loading.attr="disabled"
                                wire:target="sign({{ $document->id }})"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="sign({{ $document->id }})">Sign</span>
                                <span wire:loading wire:target="sign({{ $document->id }})">Signing…</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-16 text-center text-sm text-gray-400 dark:text-gray-500">
                    Nothing is waiting for your signature.
                </p>
            @endforelse

            @if ($documents->hasPages())
                <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>


    {{-- ================================================================= --}}
    {{-- Start signing session --}}
    {{-- ================================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-signing-session-modal.window="open = true; $nextTick(() => $refs.pin?.focus())"
        x-on:close-signing-session-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="signingSessionModalLabel"
    >
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>

        <form wire:submit="openSession" @click.stop class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl">

            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 id="signingSessionModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Start signing session</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Authorise once; each document then takes a single click, up to {{ $maxDocuments }} documents.
                </p>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="sessionPin" class="{{ $label }}">Signing PIN <span class="text-red-500">*</span></label>
                    <input id="sessionPin" x-ref="pin" type="password" wire:model="pin" inputmode="numeric" autocomplete="off"
                        maxlength="{{ \App\Models\SigningPin::MAX_LENGTH }}" class="{{ $input }} tracking-widest">
                    @error('pin') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                @if ($codeNeeded)
                    <div>
                        <label for="sessionCode" class="{{ $label }}">Authenticator code <span class="text-red-500">*</span></label>
                        <input id="sessionCode" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code"
                            maxlength="10" class="{{ $input }} tracking-widest">
                        @error('code') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-2.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5">
                        <input type="checkbox" wire:model="remember" class="mt-0.5 size-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span class="text-xs text-gray-600 dark:text-gray-300">
                            <span class="font-semibold text-gray-800 dark:text-gray-100">Remember this device for {{ \App\Models\SigningDevice::DAYS }} days.</span>
                            Later sessions on this device need only your PIN. Use it on your own computer, never a shared one.
                        </span>
                    </label>
                @else
                    <p class="rounded-lg bg-emerald-50 dark:bg-emerald-900/30 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-300">
                        This device is remembered, so your PIN is enough.
                    </p>
                @endif

                @error('signature') <p class="{{ $error }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="openSession"
                    class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="openSession">Start session</span>
                    <span wire:loading wire:target="openSession">Opening…</span>
                </button>
            </div>
        </form>
    </div>
</div>
