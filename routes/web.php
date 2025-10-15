<?php

use App\Livewire\CustomsData\Index;
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

// FE for Datatables
Route::prefix('data')->name('data.')
    ->middleware([])
    ->group(function (): void {
        // DataTables
        Route::get('/', Index::class)->name('index');
    });



// Polling Export Status
Route::get('/export/status', function (Request $request) {
    $sessionId = $request->session()->getId();
    $data = Cache::get("export-result-{$sessionId}");
    if (!$data) {
        return response()->json(['status' => 'pending']);
    }
    return response()->json([
        'status' => 'ready',
        'url' => $data['url'],
        'file' => $data['file'],
    ]);
})->name('exports.status');

// Download Exported File
Route::get('/download/{path}', function ($path) {
    $path = urldecode($path); // decode lại trước khi dùng
    $file = storage_path('app/' . $path);
    abort_unless(file_exists($file), 404);
    return response()->download($file);
})->where('path', '.*')->name('exports.download')->middleware('signed');

Route::get('/test-download', function () {
    $filePath = storage_path('app/dlhq_exports/dlhq_20240624_153530.xlsx');
    $relativePath = 'dlhq_exports/' . basename($filePath);
    $relativePathEncoded = str_replace('/', '%2F', $relativePath);

    $signedUrl = URL::temporarySignedRoute(
        'exports.download',
        now()->addMinutes(5),
        ['path' => $relativePathEncoded]
    );
    return $signedUrl;
});
