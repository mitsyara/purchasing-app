<?php

namespace App\Traits;

use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasInventoryTransactions
{
    /**
     * Get all of the model's inventory transactions.
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'sourceable');
    }
}
