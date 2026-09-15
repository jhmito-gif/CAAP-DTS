<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sign Document') }}
        </h2>
    </x-slot>

    @vite(['resources/js/esign.js'])

    <livewire:sign-document :signature-request-id="$signatureRequest->id" />
</x-app-layout>
