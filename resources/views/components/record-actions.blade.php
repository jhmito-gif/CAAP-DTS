@props([
    'record',
    'variant' => 'row', // 'row' (list tables) | 'header' (record page action bar)
])

@php
    $user = auth()->user();
    $isCreator = $record->isCreatedBy($user);
    $canModify = (bool) $user?->can('update', $record);
    $lockedHint = 'Locked: already received by another office';

    $tooltip = 'pointer-events-none absolute left-1/2 top-full z-30 mt-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:!opacity-100';
    $pencil = 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10';
    $trash = 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0';
@endphp

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

        {{-- Delete --}}
        <button
            type="button"
            @if ($canModify) @click="$wire.dispatch('delete-record', { recordId: {{ $record->id }} })" @else aria-disabled="true" @endif
            aria-label="{{ $canModify ? 'Delete this record' : $lockedHint }}"
            class="group relative inline-flex size-10 items-center justify-center rounded-lg border transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $canModify
                ? 'border-red-200 dark:border-red-800 bg-white dark:!bg-gray-800 text-red-600 dark:text-red-400 hover:-translate-y-0.5 hover:border-red-600 hover:bg-red-600 hover:text-white hover:shadow-md focus:ring-red-500 active:translate-y-0 active:shadow-sm'
                : 'cursor-not-allowed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 focus:ring-gray-400' }}"
        >
            <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trash }}" />
            </svg>

            <span class="{{ $tooltip }}">
                {{ $canModify ? 'Delete' : $lockedHint }}
            </span>
        </button>

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

            <button
                type="button"
                @if ($canModify) @click="$wire.dispatch('delete-record', { recordId: {{ $record->id }} })" @else aria-disabled="true" @endif
                title="{{ $canModify ? 'Delete' : $lockedHint }}"
                aria-label="{{ $canModify ? 'Delete this record' : $lockedHint }}"
                class="inline-flex size-8 items-center justify-center rounded-md border shadow-sm transition {{ $canModify
                    ? 'border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 text-gray-500 dark:text-gray-400 hover:border-red-300 dark:hover:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/40 hover:text-red-600 dark:hover:text-red-400'
                    : 'cursor-not-allowed border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 text-gray-300 dark:text-gray-600' }}"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trash }}" />
                </svg>
            </button>

        </span>

    @endif

@endif
