<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class SyncShipmentsInfo
{
    public function __construct(public PurchaseOrder $order)
    {
        $order->purchaseShipments()
            ->update([
                'company_id' => $order->company_id,
                'currency' => $order->currency,
                'staff_buy_id' => $order->staff_buy_id,

                'supplier_id' => $order->supplier_id,
                'supplier_contract_id' => $order->supplier_contract_id,
                'supplier_payment_id' => $order->supplier_payment_id,
            ]);
    }
}
