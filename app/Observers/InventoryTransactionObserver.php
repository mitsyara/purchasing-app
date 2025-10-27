<?php

namespace App\Observers;

use App\Models\InventoryTransaction;

class InventoryTransactionObserver
{
    /**
     * Handle the InventoryTransaction "saved" event.
     */
    public function saved(InventoryTransaction $transaction): void
    {
        if ($transaction->descendants()->exists()) {
            new \App\Services\InventoryLine\SyncInfoToDescendants($transaction);
        }
    }
}
