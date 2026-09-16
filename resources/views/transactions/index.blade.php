<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Logs') }}
        </h2>
    </x-slot>

    <livewire:pending-signatures :record-id="$recordID" />
    <livewire:transaction-table :record-id="$recordID" />
    <livewire:internal-trail :record-id="$recordID" />
    <livewire:manage-record :redirect-after-delete="true" />
    <livewire:assign-reference />
</x-app-layout>