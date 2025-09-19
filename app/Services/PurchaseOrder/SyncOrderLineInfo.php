<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class SyncOrderLineInfo
{
    /**
     * Create a new class instance.
     */
    public function __construct(public PurchaseOrder $order)
    {
        if ($order) {
            $order->purchaseOrderLines()
                ->update([
                    'company_id' => $order->company_id,
                    'warehouse_id' => $order->warehouse_id,
                    'currency' => $order->currency,
                ]);
        }
    }
}
