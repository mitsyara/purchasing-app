<?php

namespace App\Listeners;

use Filament\Actions\Imports\Events\ImportCompleted;
use Livewire\Component;

class FilamentJobImportCompleted
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportCompleted $event): void
    {
        // Re-calculate aggregates CustomData's Categories.
        \App\Jobs\RecalculateCustomsDataAggregatesJob::dispatch();
    }
}
