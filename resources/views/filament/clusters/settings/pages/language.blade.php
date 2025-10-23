<x-filament-panels::page>
    <div class="pt-4 flex justify-end space-x-4">
        {{ $this->create }}
    </div>

    <form wire:submit="save">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
