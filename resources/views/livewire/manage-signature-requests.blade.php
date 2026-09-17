@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20';
@endphp

{{-- Request signatures for a PDF: assign signatories and track who has signed. --}}
<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-signature-requests-modal.window="open = true"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="signatureRequestsModalLabel"
>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition
        @click.stop
        class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
    >

        <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800 px-6 py-4">
            <div class="min-w-0">
                <h5 id="signatureRequestsModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Signatures
                </h5>

                @if ($attachment)
                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                        {{ $attachment->displayNameFor(auth()->user()) }} &middot; {{ $attachment->record?->reference }}
                    </p>
                @endif
            </div>

            <button
                type="button"
                @click="open = false"
                class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
                aria-label="Close"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">

            @if ($attachment)

                {{-- Assigned signatories --}}
                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Signatories
                        @if ($attachment->signatureRequests->isNotEmpty())
                            <span class="font-normal text-gray-400 dark:text-gray-500">
                                &middot; {{ $attachment->signatureRequests->whereNotNull('signed_at')->count() }} of {{ $attachment->signatureRequests->count() }} signed
                            </span>
                        @endif
                    </p>

                    @forelse ($attachment->signatureRequests as $assignment)
                        <div
                            wire:key="signature-request-{{ $assignment->id }}"
                            class="mb-1.5 flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $assignment->signer?->name ?? 'Deleted user' }}
                                </p>
                                <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                    {{ $assignment->signer?->office }}
                                </p>
                            </div>

                            @if ($assignment->isPending())
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                                        Pending
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="cancelRequest({{ $assignment->id }})"
                                        class="text-[11px] font-semibold text-red-600 hover:underline dark:text-red-400"
                                    >
                                        Remove
                                    </button>
                                </div>
                            @else
                                <span
                                    class="shrink-0 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300"
                                    title="{{ $assignment->signed_at->copy()->setTimezone('Asia/Manila')->format('j M Y g:i A') }}"
                                >
                                    Signed
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                            No one has been asked to sign yet.
                        </p>
                    @endforelse
                </div>

                @if ($attachment->isSigningComplete())

                    <p class="rounded-lg bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300">
                        Every signatory has signed. The document is locked.
                    </p>

                @else

                    {{-- Add signatories --}}
                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Request a signature
                        </p>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <select wire:model.live="office" class="{{ $input }}" aria-label="Filter by office">
                                <option value="">All offices</option>
                                @foreach ($officeOptions as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>

                            <input
                                type="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search by name"
                                class="{{ $input }}"
                            >
                        </div>

                        <div class="mt-2 max-h-56 divide-y divide-gray-100 dark:divide-gray-800 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            @if ($search === '' && $office === '')
                                <p class="px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                                    Pick an office or search for a name.
                                </p>
                            @else
                                @forelse ($candidates as $candidate)
                                    <div wire:key="candidate-{{ $candidate->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $candidate->name }}</p>
                                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $candidate->service ?: $candidate->office }}</p>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="requestSignature({{ $candidate->id }})"
                                            wire:loading.attr="disabled"
                                            class="shrink-0 rounded-md bg-sky-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:opacity-60"
                                        >
                                            Request
                                        </button>
                                    </div>
                                @empty
                                    <p class="px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-500">
                                        No matching people.
                                    </p>
                                @endforelse
                            @endif
                        </div>

                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            Signers are notified and can open this record. They need two-factor authentication and a saved signature to sign.
                        </p>
                    </div>

                @endif

            @endif

        </div>

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 px-6 py-3">
            <a href="{{ route('esign.verify') }}" class="text-xs font-semibold text-sky-600 hover:underline dark:text-sky-400">
                Verify a signature
            </a>

            <button
                type="button"
                @click="open = false"
                class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                Done
            </button>
        </div>

    </div>
</div>
