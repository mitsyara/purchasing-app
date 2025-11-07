<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Services\Core\InventoryService;

class InventoryTransactionObserver
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * Handle the InventoryTransaction "saved" event.
     */
    public function saved(InventoryTransaction $transaction): void
    {
        if ($transaction->descendants()->exists()) {
            $this->inventoryService->syncInfoToDescendants($transaction);
        }
    }
}
