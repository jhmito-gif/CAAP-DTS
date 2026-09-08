<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Incoming Records') }}
        </h2>
    </x-slot>
    <livewire:incoming-table>
    <livewire:create-incoming>
</x-app-layout>
