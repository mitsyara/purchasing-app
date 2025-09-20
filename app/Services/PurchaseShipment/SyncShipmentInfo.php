<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class SyncShipmentInfo
{
    public function __construct(public PurchaseShipment $shipment)
    {
        $order = $shipment->purchaseOrder;

        $shipment->updateQuietly([
            'company_id' => $order->company_id,
            'currency' => $order->currency,
            'staff_buy_id' => $order->staff_buy_id,

            'supplier_id' => $order->supplier_id,
            'supplier_contract_id' => $order->supplier_contract_id,
            'supplier_payment_id' => $order->supplier_payment_id,
        ]);
    }
}
