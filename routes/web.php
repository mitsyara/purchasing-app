<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;

// Route::view('/', 'welcome')->name('home');

Route::get('/', function () {
    $panel = Filament::getCurrentOrDefaultPanel();
    return redirect($panel->getLoginUrl(['tenant' => $panel]));
});


// Test cron-job
Route::get('/test-schedule', function () {
    \App\Jobs\TestCronConnectionJob::dispatch();
    return redirect()->back();
});

Route::get('/cache', function () {
    \Illuminate\Support\Facades\Cache::rememberForever(
        'customs_data_categories.all',
        function (): \Illuminate\Database\Eloquent\Collection {
            return \App\Models\CustomsDataCategory::all(['id', 'name', 'keywords']);
        }
    );

    return redirect()->back();
});
