<div x-data="{ open: @entangle('open') }" x-show="open" x-cloak class="screen-lock-overlay"
    @keydown.window="
        if(open && $event.target.tagName !== 'INPUT' && $event.target.tagName !== 'TEXTAREA') {
            $event.preventDefault()
        }
     "
    @contextmenu.window="if(open) $event.preventDefault()" @trigger-lock.window="open = true">

    <div class="screen-lock-box">
        <h2 class="screen-lock-title">{{ __('Screen Locked') }}</h2>

        <x-filament::input.wrapper :valid="!$errors->has('lock_pin')">
            <x-filament::input type="password" id="screen-lock-pin-input" wire:model.defer="lock_pin" placeholder="{{ __('Enter PIN') }}"
                @keydown.enter="$wire.unlock()" />
        </x-filament::input.wrapper>

        <x-filament::button wire:click="unlock" class="mt-4 w-full">
            {{ __('Unlock') }}
        </x-filament::button>

        <p class="screen-lock-error">{{ $error }}</p>
    </div>
</div>
