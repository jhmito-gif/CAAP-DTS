@php
    $assignments = $attachment?->signatureRequests ?? collect();
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20';
    $error = 'mt-1.5 text-xs font-medium text-red-600 dark:text-red-400';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ============================================================= --}}
        {{-- DOCUMENT --}}
        {{-- ============================================================= --}}
        <div class="min-w-0 lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 shadow-sm">

                <div class="border-b border-gray-100 dark:border-gray-700 px-4 py-3">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $attachment?->original_name ?? 'Document' }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $record?->reference }}
                    </p>
                </div>

                @if ($tokenRequired && ! $unlocked)

                    <form wire:submit="unlock" class="mx-auto max-w-sm space-y-3 px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">This record is confidential</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enter the record's access token to open the document.</p>

                        <input type="password" wire:model="unlockToken" autocomplete="off" placeholder="Access token" class="{{ $input }}">

                        @error('unlockToken')
                            <p class="{{ $error }}">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Unlock
                        </button>
                    </form>

                @elseif (! $documentUrl)

                    <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        You cannot open this document.
                    </p>

                @else

                    <div
                        wire:ignore
                        wire:key="esign-viewer-{{ $unlocked ? 'unlocked' : 'open' }}"
                        x-data="esignViewer({ url: @js($documentUrl), canPlace: @js($blocker === null), signature: @js($signaturePreview) })"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="go(-1)" :disabled="pageNumber <= 1" class="rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 font-semibold disabled:opacity-40">
                                    Prev
                                </button>
                                <span>Page <span x-text="pageNumber"></span> of <span x-text="pageCount || '…'"></span></span>
                                <button type="button" @click="go(1)" :disabled="pageNumber >= pageCount" class="rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 font-semibold disabled:opacity-40">
                                    Next
                                </button>
                            </div>

                            <p x-show="canPlace && ! rotated">Click the page to place your signature. Drag to move; use the corner to resize.</p>
                            <p x-show="rotated" x-cloak class="font-semibold text-amber-600 dark:text-amber-400">This page is rotated and cannot be signed.</p>
                        </div>

                        <div x-ref="stage" class="relative max-h-[78vh] overflow-auto bg-gray-100 dark:bg-gray-900 p-4">
                            <p x-show="loading" class="py-24 text-center text-sm text-gray-500">Loading document…</p>
                            <p x-show="error" x-cloak x-text="error" class="py-24 text-center text-sm text-red-600"></p>

                            <div x-show="! loading && ! error" class="relative mx-auto" :style="`width: ${canvasWidth}px`">
                                <canvas
                                    x-ref="canvas"
                                    @click="place($event)"
                                    :class="canPlace && ! rotated ? 'cursor-crosshair' : ''"
                                    class="block bg-white shadow"
                                ></canvas>

                                <div
                                    x-show="boxVisible"
                                    x-cloak
                                    :style="boxStyle"
                                    @pointerdown.prevent="startDrag($event, 'move')"
                                    class="absolute flex cursor-move select-none flex-col items-center justify-center rounded border-2 border-dashed border-sky-500 bg-sky-50/40"
                                    data-esign-box
                                >
                                    <img :src="signature" alt="" class="pointer-events-none max-h-[70%] max-w-full object-contain">
                                    <span class="pointer-events-none text-[9px] font-semibold text-sky-800">Your signature</span>
                                    <span
                                        @pointerdown.prevent.stop="startDrag($event, 'resize')"
                                        class="absolute -bottom-1.5 -right-1.5 size-3 cursor-se-resize rounded-sm bg-sky-600"
                                    ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                @endif

            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- SIGNING PANEL --}}
        {{-- ============================================================= --}}
        <div class="space-y-4">

            {{-- Signatories --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-4 shadow-sm">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Signatories</p>

                <ul class="mt-2 space-y-1.5">
                    @foreach ($assignments as $assignment)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate text-gray-700 dark:text-gray-200">
                                {{ $assignment->signer?->name ?? 'Deleted user' }}
                                @if ($assignment->id === $signatureRequest->id)
                                    <span class="text-xs text-gray-400">(you)</span>
                                @endif
                            </span>

                            @if ($assignment->isPending())
                                <span class="shrink-0 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-300">Pending</span>
                            @else
                                <span class="shrink-0 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">Signed</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($blocker)

                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-300">
                    <p class="font-semibold">You can't sign right now</p>
                    <p class="mt-1">{{ $blocker }}</p>

                    @if (str_contains($blocker, 'profile'))
                        <a href="{{ route('profile.show') }}" class="mt-2 inline-block font-semibold text-amber-900 underline dark:text-amber-200">
                            Open your profile
                        </a>
                    @endif
                </div>

            @elseif (! $tokenRequired || $unlocked)

                <form wire:submit="sign" class="space-y-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-4 shadow-sm">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Your signature</p>

                        <div class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white px-3 py-2">
                            <img src="{{ $signaturePreview }}" alt="Your saved signature" class="h-12 w-auto">
                        </div>

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            It is stamped with your name, the date and a verification code. The original file is kept.
                        </p>

                        @error('page') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        @error('width') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        @error('height') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="esignPin" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Signing PIN</label>
                        <input id="esignPin" type="password" wire:model="pin" inputmode="numeric" autocomplete="off" maxlength="{{ \App\Models\SigningPin::MAX_LENGTH }}" class="{{ $input }} tracking-widest">
                        @error('pin') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    @if ($codeRequired)
                        <div>
                            <label for="esignCode" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Authenticator code</label>
                            <input id="esignCode" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="{{ $input }} tracking-widest">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Needed once per session. Until you log out, your next signatures only need your PIN.</p>
                            @error('code') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="flex items-start gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-300">
                            <svg class="mt-px size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            <span>Authenticator already confirmed this session. Your signing PIN is enough.</span>
                        </p>
                        @error('code') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    @endif

                    @error('signature')
                        <p class="rounded-lg bg-red-50 dark:bg-red-900/30 px-3 py-2 text-xs font-medium text-red-700 dark:text-red-300">{{ $message }}</p>
                    @enderror

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="sign"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span wire:loading.remove wire:target="sign">Sign document</span>
                        <span wire:loading wire:target="sign">Signing…</span>
                    </button>
                </form>

            @endif

            <a href="{{ route('esign.verify') }}" class="block text-center text-xs font-semibold text-sky-600 hover:underline dark:text-sky-400">
                Verify a signature
            </a>
        </div>

    </div>
</div>
