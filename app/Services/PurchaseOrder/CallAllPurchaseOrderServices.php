<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class CallAllPurchaseOrderServices
{
    public function __construct(public PurchaseOrder $order)
    {
        new SyncOrderLinesInfo($order);
        new SyncShipmentsInfo($order);
        new UpdateOrderTotals($order);

        // TODO: calculate order's received / paid values
    }
}
