<?php

namespace App\Observers;

use App\Models\PurchaseOrderLine;

class PurchaseOrderLineObserver
{
    /**
     * Handle the PurchaseOrderLine "created" event.
     */
    public function created(PurchaseOrderLine $purchaseOrderLine): void
    {
        //
    }

    /**
     * Handle the PurchaseOrderLine "updated" event.
     */
    public function updated(PurchaseOrderLine $purchaseOrderLine): void
    {
        //
    }

    /**
     * Handle the PurchaseOrderLine "deleted" event.
     */
    public function deleted(PurchaseOrderLine $purchaseOrderLine): void
    {
        //
    }

    /**
     * Handle the PurchaseOrderLine "restored" event.
     */
    public function restored(PurchaseOrderLine $purchaseOrderLine): void
    {
        //
    }

    /**
     * Handle the PurchaseOrderLine "force deleted" event.
     */
    public function forceDeleted(PurchaseOrderLine $purchaseOrderLine): void
    {
        //
    }
}
