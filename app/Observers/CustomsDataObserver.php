<?php

namespace App\Observers;

use App\Models\CustomsData;

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
    }
}
