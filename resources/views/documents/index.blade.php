<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Documents') }}
        </h2>
    </x-slot>

    <livewire:document-explorer />

    {{-- Documents open in floating windows over the explorer. --}}
    @include('partials.document-windows')

    @vite('resources/js/viewer.js')

</x-app-layout>
