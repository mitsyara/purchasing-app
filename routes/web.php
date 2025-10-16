<?php

use App\Livewire\CustomsData\Index;
use App\Livewire\CustomsData\PinForm;
use App\Services\VcbExchangeRatesService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

Route::get('/', fn() => view('welcome'));

// Test cron-job
Route::get('/test-schedule', function () {
    \App\Jobs\TestCronConnectionJob::dispatch();
    return redirect()->back();
});

Route::get('/data', Index::class)->name('index')
    ->middleware([\App\Http\Middleware\CheckPin::class]);

Route::get('/lock-screen', PinForm::class)->name('pin.form');

// Polling Export Status
Route::get('/export/status', function (Request $request) {
    $sessionId = $request->session()->getId();
    $data = Cache::get("export-result-{$sessionId}");

    if ($data && $data['status'] === 'ready') {
        return response()->json([
            'status' => 'ready',
            'url' => $data['url'],
            'file' => $data['file'],
        ]);
    }

    if ($data && $data['status'] === 'failed') {
        return response()->json([
            'status' => 'failed',
            'message' => $data['message'],
        ]);
    }

    return response()->json(['status' => 'pending']);
})->name('exports.status');

// Download Exported File
Route::get('/download/{path}', function ($path) {
    $path = urldecode($path); // decode lại trước khi dùng

    $file = storage_path('app/' . $path);

    abort_unless(file_exists($file), 404);

    return response()->download($file);
})->where('path', '.*')->name('exports.download')->middleware('signed');

Route::get('/session', function (Request $request) {
    $key = $request->session()->getId();
    $condition = session()->has("export-result-{$key}");
    dd($key, $condition);
});

Route::get('/test-download', function () {
    $relativePath = 'dlhq_exports/test.xlsx';
    $signedUrl = URL::temporarySignedRoute(
        'exports.download',
        now()->addMinutes(5),
        ['path' => $relativePath]
    );
    return $signedUrl;
});

Route::get('/exrate', function () {
    $rates = VcbExchangeRatesService::fetch();
    dd($rates);
});
