<?php

namespace App\Listeners;

use Filament\Actions\Imports\Events\ImportCompleted;
use Illuminate\Support\Facades\Cache;

class FilamentJobImportCompleted
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ImportCompleted $event): void
    {
        // Cache CustomData count
        Cache::rememberForever('customs_data_count', function () {
            return \App\Models\CustomsData::count();
        });

        // Re-calculate aggregates CustomData's Categories.
        \App\Jobs\RecalculateCustomsDataAggregatesJob::dispatch();
    }
}
