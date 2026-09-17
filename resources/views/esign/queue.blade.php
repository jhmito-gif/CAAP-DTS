<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Documents for Signature') }}
        </h2>
    </x-slot>

    @vite(['resources/js/esign.js'])

    <livewire:signature-queue />

</x-app-layout>
