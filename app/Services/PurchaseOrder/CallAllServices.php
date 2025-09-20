<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class CallAllServices
{
    /**
     * Create a new class instance.
     */
    public function __construct(public PurchaseOrder $order)
    {
        new UpdateOrderTotals($order);
        new SyncOrderLinesInfo($order);
    }
}
