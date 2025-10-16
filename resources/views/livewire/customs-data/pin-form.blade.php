<div class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
    <form wire:submit="submit" class="w-full max-w-[400px] bg-white dark:bg-gray-800 p-6 rounded shadow">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-6 mx-auto block">
            {{ __('Unlock') }}
        </x-filament::button>
    </form>
</div>