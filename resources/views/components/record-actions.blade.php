@props([
    'record',
    'variant' => 'row', // 'row' (list tables) | 'header' (record page action bar)
])

@php
    $user = auth()->user();
    $isCreator = $record->isCreatedBy($user);
    $canModify = (bool) $user?->can('update', $record);
    $canResetToken = (bool) $user?->can('resetAccessToken', $record);
    $tokenLabel = $record->requiresToken() ? 'Reset access token' : 'Set access token';
    $lockedHint = 'Locked: already received by another office';

    $tooltip = 'pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:!opacity-100';
    $pencil = 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10';
@endphp

{{-- Records are never deleted -- a mistake is corrected by editing, and every change is in the audit log. --}}

{{-- Only the creator (possibly locked) and admins see these controls. --}}
@if ($canModify || $isCreator)

    @if ($variant === 'header')

        {{-- Edit --}}
        <button
            type="button"
            @if ($canModify) @click="$wire.dispatch('edit-record', { recordId: {{ $record->id }} })" @else aria-disabled="true" @endif
            aria-label="{{ $canModify ? 'Edit this record' : $lockedHint }}"
            class="group relative inline-flex size-10 items-center justify-center rounded-lg border transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $canModify
                ? 'border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 hover:-translate-y-0.5 hover:border-sky-600 hover:bg-sky-600 hover:text-white hover:shadow-md focus:ring-sky-500 active:translate-y-0 active:shadow-sm'
                : 'cursor-not-allowed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 focus:ring-gray-400' }}"
        >
            <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pencil }}" />
            </svg>

            <span class="{{ $tooltip }}">
                {{ $canModify ? 'Edit' : $lockedHint }}
            </span>
        </button>

        {{-- Reset a confidential record's access token (creator and admins, even once received). --}}
        @if ($canResetToken)
            <button
                type="button"
                @click="$wire.dispatch('reset-access-token', { recordId: {{ $record->id }} })"
                aria-label="{{ $tokenLabel }}"
                class="group relative inline-flex size-10 items-center justify-center rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 transition-all duration-150 hover:-translate-y-0.5 hover:border-rose-600 hover:bg-rose-600 hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 active:translate-y-0 active:shadow-sm"
            >
                <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H9v1.5H7.5v1.5H6a1.5 1.5 0 0 1-1.5-1.5v-1.629c0-.398.158-.78.44-1.062l5.616-5.615a6 6 0 1 1 8.909-4.494Z" />
                </svg>

                <span class="{{ $tooltip }}">{{ $tokenLabel }}</span>
            </button>
        @endif

    @else

        {{-- Row variant: stop clicks from opening the row's record page. --}}
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }} onclick="event.stopPropagation()">

            <button
                type="button"
                @if ($canModify) @click="$wire.dispatch('edit-record', { recordId: {{ $record->id }} })" @else aria-disabled="true" @endif
                title="{{ $canModify ? 'Edit' : $lockedHint }}"
                aria-label="{{ $canModify ? 'Edit this record' : $lockedHint }}"
                class="inline-flex size-8 items-center justify-center rounded-md border shadow-sm transition {{ $canModify
                    ? 'border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-500 dark:text-gray-400 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-sky-50 dark:hover:bg-sky-900/40 hover:text-sky-700 dark:hover:text-sky-300'
                    : 'cursor-not-allowed border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 text-gray-300 dark:text-gray-600' }}"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pencil }}" />
                </svg>
            </button>

        </span>

    @endif

@endif
