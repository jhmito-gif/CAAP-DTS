<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Outgoing Records') }}
        </h2>
    </x-slot>
 


    {{-- The send form lets the sender mark signature positions on the uploaded PDF. --}}
    @vite(['resources/js/esign.js'])

    <livewire:outgoing-table/>
    <livewire:create-outgoing/>
    <livewire:manage-record/>

</x-app-layout>
