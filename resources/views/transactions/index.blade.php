<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Logs') }}
        </h2>
    </x-slot>

    <livewire:transaction-table :record-id="$recordID" />
    <livewire:manage-record :redirect-after-delete="true" />
    <livewire:receive-transaction />
</x-app-layout>