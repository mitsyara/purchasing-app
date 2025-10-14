<?php

use App\Models\User;
use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature/Filament');

dataset('users', [
    // email, password, shouldPass
    ['test@vietuy.vn', 'password', false], // inactive
    ['test@vhl.com.vn', 'password', true], // active
    ['test@globalhub.com.vn', 'password', false], // suspended
    ['test@cangroup.vn', 'password', true], // active
]);

it('allows or rejects login depending on user status', function ($email, $password, $shouldPass) {
    $now = now();
    // Arrange: tạo user với trạng thái active/inactive/suspended
    if ($email === 'test@vietuy.vn') {
        $user = User::factory()->inactive()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    } elseif ($email === 'test@vhl.com.vn') {
        $user = User::factory()->active()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => $now,
        ]);
    } elseif ($email === 'test@globalhub.com.vn') {
        $user = User::factory()->suspended()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    } elseif ($email === 'test@cangroup.vn') {
        $user = User::factory()->active()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => $now,
        ]);
    } else {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    // Act: dùng Livewire test login
    $livewireTest = Livewire::test(FilamentLogin::class)
        ->set('email', $email)
        ->set('password', $password)
        ->call('authenticate');

    // Assert
    if ($shouldPass) {
        $livewireTest->assertRedirect(Filament::getHomeUrl());
        $this->assertAuthenticatedAs($user, 'web');
    } else {
        $livewireTest->assertRedirect(Filament::getLoginUrl());
        $this->assertGuest();
    }
})->with('users');
