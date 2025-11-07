<?php

namespace App\Observers;

use App\Models\PurchaseShipment;
use App\Services\Core\PurchaseOrderService;

class PurchaseShipmentObserver
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService
    ) {}

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
        $this->purchaseOrderService->updateTotals($order->id);
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
