<?php

namespace App\Providers;

use App\Livewire\MyProfile\PersonalInfoComponent;
use App\Livewire\MyProfile\UpdatePasswordComponent;
use Illuminate\Support\ServiceProvider;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // \Livewire\Livewire::component('personal_info_component', PersonalInfoComponent::class);
        // \Livewire\Livewire::component('personal-info-component', PersonalInfoComponent::class);
        // \Livewire\Livewire::component('update-password-component', UpdatePasswordComponent::class);
        // \Livewire\Livewire::component('update_password_component', UpdatePasswordComponent::class);
    }
}
