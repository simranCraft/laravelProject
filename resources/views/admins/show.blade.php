<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Details') }}
        </h2>
    </x-slot>

    @include('shared.show-data', ['data' => $user])
</x-app-layout>
