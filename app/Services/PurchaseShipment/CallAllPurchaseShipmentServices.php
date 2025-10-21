<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class CallAllPurchaseShipmentServices
{
    public function __construct(public PurchaseShipment $shipment)
    {
        // Process related order
        $order = $shipment->purchaseOrder;
        if (!$order->order_number) {
            $order->order_number = $order->generateOrderNumber();
        }
        if (!$order->order_date) {
            $order->order_date = now();
        }
        if ($order->order_status === \App\Enums\OrderStatusEnum::Draft) {
            $order->order_status = \App\Enums\OrderStatusEnum::Inprogress;
        }
        $order->save();

        new SyncShipmentInfo($shipment);
        new SyncShipmentLinesInfo($shipment);
        new UpdateShipmentTotals($shipment);
    }
}
