<?php

namespace App\Livewire;

use Livewire\Component;

class DisplayAppPin extends Component
{
    public ?string $appPin = null;

    public function mount(): void
    {
        $this->appPin = \Illuminate\Support\Facades\Cache::get('app_pin', '1234');
    }

    #[\Livewire\Attributes\On('refresh-app-pin')]
    public function refreshPin(): void
    {
        $this->appPin = \Illuminate\Support\Facades\Cache::get('app_pin', '1234');
    }

    public function render()
    {
        return <<<'HTML'
        <div class="mx-2">
            <x-filament::badge size="xl" color="info">
                {{ $appPin }}
            </x-filament::badge>

            <x-filament::link href="{{ \App\Filament\Pages\UserGuide::getUrl() }}" color="info" size="sm">
                {{ __('User Guide') }}
            </x-filament::link>
        </div>
        HTML;
    }
}
