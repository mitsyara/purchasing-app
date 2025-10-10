<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

class ScreenLockModal extends Component
{
    public $lock_pin = '';
    public $error = '';
    public $open = false;

    protected $rateLimiter;

    public function mount(RateLimiter $rateLimiter)
    {
        $this->open = session('screen_locked', false);
        $this->rateLimiter = $rateLimiter;
    }

    public function unlock(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->error = 'Not authenticated.';
            return;
        }

        if ($user->lock_pin && Hash::check($this->lock_pin, $user->lock_pin)) {
            session()->forget('screen_locked');
            $this->open = false;
            $this->lock_pin = '';
            $this->error = '';
        } else {
            $this->error = 'Wrong PIN.';
        }
    }

    public function lock(): void
    {
        session(['screen_locked' => true]);
        $this->open = true;
    }

    public function render()
    {
        return view('livewire.screen-lock-modal');
    }
}
