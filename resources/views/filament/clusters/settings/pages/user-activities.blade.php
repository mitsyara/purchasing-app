<x-filament-panels::page x-data="{ activeTab: $wire.entangle('activeTab') }">

    <x-filament::tabs label="Content tabs">
        <x-filament::tabs.item x-on:click="activeTab = 'activities'" :alpine-active="'activeTab === \'activities\''">
            Activities
        </x-filament::tabs.item>

        <x-filament::tabs.item x-on:click="activeTab = 'authentication'" :alpine-active="'activeTab === \'authentication\''">
            Authentication
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div>
        <div x-show="activeTab === 'activities'">
            <livewire:user-activity-log lazy />
        </div>

        <div x-show="activeTab === 'authentication'">
            <livewire:user-authentication-log lazy />
        </div>
    </div>

</x-filament-panels::page>
