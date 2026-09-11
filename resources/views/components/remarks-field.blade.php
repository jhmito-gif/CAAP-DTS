@props([
    'model' => 'remarks',
    'label' => 'Remarks',
    'required' => false,
    'id' => null,
    'rows' => 3,
    'placeholder' => 'Enter remarks...',
    'accent' => 'emerald',
    'live' => false,
])

@php
    $id = $id ?? $model;
    $presets = \App\Models\RemarkTemplate::options();

    // Focus-ring accent (emerald by default; blue for the send modals).
    $ring = [
        'emerald' => 'focus:border-emerald-500 focus:ring-emerald-500/20',
        'blue' => 'focus:border-blue-500 focus:ring-blue-500/20',
    ][$accent] ?? 'focus:border-emerald-500 focus:ring-emerald-500/20';
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
        {{ $label }}
        @if ($required)
            <span class="text-red-500 dark:text-red-400">*</span>
        @endif
    </label>

    @if ($presets->isNotEmpty())
        {{-- Preset picker: fills the box, then you can edit or add to it. --}}
        <select
            x-on:change="if ($event.target.value !== '') { $wire.set('{{ $model }}', $event.target.value); $event.target.value = '' }"
            class="mb-1.5 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 shadow-sm transition focus:outline-none focus:ring-2 {{ $ring }}"
            aria-label="Insert a preset remark"
        >
            <option value="">＋ Insert a preset remark…</option>
            @foreach ($presets as $preset)
                <option value="{{ $preset }}">{{ $preset }}</option>
            @endforeach
        </select>
    @endif

    <textarea
        @if ($live) wire:model.live.debounce.300ms="{{ $model }}" @else wire:model="{{ $model }}" @endif
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full resize-none rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm transition placeholder:text-gray-400 focus:outline-none focus:ring-2 ' . $ring]) }}
    ></textarea>

    @error($model)
        <div class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            {{ $message }}
        </div>
    @enderror
</div>
