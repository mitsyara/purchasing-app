<x-filament-panels::page x-data="{ activePanel: $wire.entangle('activePanel') }">

    <x-filament::tabs label="Content tabs">
        <x-filament::tabs.item x-on:click="activePanel = 'activities'" :alpine-active="'activePanel === \'activities\''">
            Activities
        </x-filament::tabs.item>

        <x-filament::tabs.item x-on:click="activePanel = 'authentication'" :alpine-active="'activePanel === \'authentication\''">
            Authentication
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div>
        <div x-show="activePanel === 'activities'">
            <livewire:user-activity-log lazy />
        </div>

        <div x-show="activePanel === 'authentication'">
            <livewire:user-authentication-log lazy />
        </div>
    </div>

</x-filament-panels::page>
