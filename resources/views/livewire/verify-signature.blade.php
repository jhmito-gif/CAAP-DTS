@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20';
    $card = 'rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 p-5 shadow-sm';
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

    {{-- By verification code --}}
    <div class="{{ $card }}">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Check a verification code</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Every signature stamp carries a code such as K7QM2-X9PDR.
        </p>

        <form wire:submit="lookup" class="mt-4 flex flex-col gap-2 sm:flex-row">
            <input type="text" wire:model="code" placeholder="Verification code" autocomplete="off" class="{{ $input }} uppercase tracking-widest">
            <button type="submit" class="shrink-0 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                Check
            </button>
        </form>

        @error('code')
            <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        @if ($found)
            <div class="mt-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/20 p-4">
                <p class="mb-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">Signature found</p>
                @include('livewire.partials.signature-details', ['details' => $found])
            </div>
        @endif
    </div>

    {{-- By file --}}
    <div class="{{ $card }}">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Check a PDF file</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Upload a PDF to compare its fingerprint with every signed version. The file is not stored.
        </p>

        <form wire:submit="checkFile" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                type="file"
                wire:model="file"
                accept="application/pdf"
                class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white"
            >
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="file,checkFile"
                class="shrink-0 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60"
            >
                Check file
            </button>
        </form>

        @error('file')
            <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        @if ($fileResult === 'match' && $fileMatch)
            <div class="mt-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/20 p-4">
                <p class="mb-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                    This file matches the version signed by {{ $fileMatch['signature']->signer_name }}.
                </p>
                @include('livewire.partials.signature-details', ['details' => $fileMatch])
            </div>
        @elseif ($fileResult === 'none')
            <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-300">
                This file does not match any signed version. It may have been changed after signing, or it was never signed here.
            </div>
        @endif
    </div>

</div>
