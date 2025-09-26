<?php

namespace App\Observers;

use App\Models\CustomsData;
use Illuminate\Support\Facades\Cache;

class CustomsDataObserver
{
    /**
     * Handle the CustomsData "saved" event.
     */
    public function saved(CustomsData $customsData): void
    {
        if (!$customsData->customs_data_category_id) {
            $customsData->guessCategoryByName();
        }

        // Update the cached latest import date if the saved record has a newer date
        if ($customsData->import_date && $customsData->import_date->gt(Cache::get('customs_data_max_import_date'))) {
            Cache::put('customs_data_max_import_date', $customsData->import_date);
        }
    }
}
