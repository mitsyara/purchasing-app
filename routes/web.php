<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;

Route::get('/', function () {
    $panel = Filament::getCurrentOrDefaultPanel();
    return redirect($panel->getLoginUrl(['tenant' => $panel]));
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/lock-screen', function () {
        session(['screen_locked' => true]);
        return back();
    })->name('lock-screen');
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

Route::get('/recache-customs-data', function () {
    \App\Jobs\RecalculateCustomsDataByImporterJob::dispatch();
    \App\Jobs\RecalculateCustomsDataByImporterCategoryJob::dispatch();
    return redirect()->back();
})->middleware('auth');
