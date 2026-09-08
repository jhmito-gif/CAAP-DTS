<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Logs') }}
        </h2>
    </x-slot>

    <livewire:outgoing-transaction :record-id="$recordID" />
</x-app-layout>