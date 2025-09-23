<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;

// Route::view('/', 'welcome')->name('home');

Route::get('/', function () {
    $panel = Filament::getCurrentOrDefaultPanel();
    return redirect($panel->getLoginUrl(['tenant' => $panel]));
});
