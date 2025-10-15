<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanUpCustomsDataExportFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $filePath) {}

    public function handle(): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
            Log::channel('export')->info("Deleted export file: {$this->filePath}");
        } else {
            Log::channel('export')->warning("File already deleted or not found: {$this->filePath}");
        }
    }
}
