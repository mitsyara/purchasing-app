<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class SyncOrderLinesInfo
{
    public function __construct(public PurchaseOrder $order)
    {
        $order->purchaseOrderLines()
            ->update([
                'company_id' => $order->company_id ?? null,
                'warehouse_id' => $order->warehouse_id ?? null,
                'currency' => $order->currency ?? null,
            ]);
    }
}
