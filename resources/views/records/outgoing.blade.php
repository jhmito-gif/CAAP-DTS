<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Outgoing Records') }}
        </h2>
    </x-slot>
 


    <livewire:outgoing-table/>
    <livewire:create-outgoing/>
    <livewire:manage-record/>

</x-app-layout>
