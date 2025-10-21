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

Route::get('/lock-screen', PinForm::class)->name('pin.form');

Route::get('/data', Index::class)->name('customs-data.index');

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
    // decode lại
    $path = urldecode($path); 

    $file = storage_path('app/' . $path);

    abort_unless(file_exists($file), 404);

    return response()->download($file);
})->where('path', '.*')->name('exports.download')->middleware('signed');

Route::get('/test-download', function () {
    $relativePath = 'dlhq_exports/test.xlsx';
    $signedUrl = URL::temporarySignedRoute(
        'exports.download',
        now()->addMinutes(5),
        ['path' => $relativePath]
    );
    return $signedUrl;
});

Route::get('/test', function () {
    return view('pdf-view.price-quote-print');
});

Route::get('/print-quote', function(Request $request) {
    return view('pdf-view.price-quote-print', [
        'data' => $request->input('data', []),
    ]);
})->name('customs-data.price-quote.print');