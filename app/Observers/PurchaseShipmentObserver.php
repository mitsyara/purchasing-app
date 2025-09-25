<?php

namespace App\Observers;

use App\Models\PurchaseShipment;

class PurchaseShipmentObserver
{
    /**
     * Handle the PurchaseShipment "created" event.
     */
    public function created(PurchaseShipment $purchaseShipment): void
    {
        //
    }

    /**
     * Handle the PurchaseShipment "updated" event.
     */
    public function updated(PurchaseShipment $purchaseShipment): void
    {
        //
    }

    /**
     * Handle the PurchaseShipment "deleted" event.
     */
    public function deleted(PurchaseShipment $purchaseShipment): void
    {
        $order = $purchaseShipment->purchaseOrder;
        new \App\Services\PurchaseOrder\CallAllPurchaseOrderServices($order);
    }

    /**
     * Handle the PurchaseShipment "restored" event.
     */
    public function restored(PurchaseShipment $purchaseShipment): void
    {
        //
    }

    /**
     * Handle the PurchaseShipment "force deleted" event.
     */
    public function forceDeleted(PurchaseShipment $purchaseShipment): void
    {
        //
    }
}
