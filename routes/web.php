<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Filament\Facades\Filament;

Route::get('/', fn() => view('welcome'));

// Redirect to Filament Login
Route::get('/login', function () {
    $url = Filament::getDefaultPanel()->getLoginUrl();
    return redirect($url);
})->name('login');
