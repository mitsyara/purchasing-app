<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $panel = \Filament\Facades\Filament::getCurrentOrDefaultPanel();
    // return redirect($panel->getLoginUrl(['tenant' => $panel]));
});

Route::get('/', fn() => view('welcome'));

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
