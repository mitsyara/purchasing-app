<x-filament-panels::page x-data="{ activePanel: $wire.entangle('activePanel') }">

    <x-filament::tabs label="Content tabs">
        <x-filament::tabs.item x-on:click="activePanel = 'vat'" :alpine-active="'activePanel === \'vat\''">
            VAT
        </x-filament::tabs.item>

        <x-filament::tabs.item x-on:click="activePanel = 'unit'" :alpine-active="'activePanel === \'unit\''">
            Unit
        </x-filament::tabs.item>

        <x-filament::tabs.item x-on:click="activePanel = 'packing'" :alpine-active="'activePanel === \'packing\''">
            Packing
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div>
        <div x-show="activePanel === 'vat'">
            <livewire:vat-table-view lazy />
        </div>

        <div x-show="activePanel === 'unit'">
            <livewire:unit-table-view lazy />
        </div>

        <div x-show="activePanel === 'packing'">
            <livewire:packing-table-view lazy />
        </div>
    </div>

</x-filament-panels::page>
