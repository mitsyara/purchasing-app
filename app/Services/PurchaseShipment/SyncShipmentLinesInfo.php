<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;

class SyncShipmentLinesInfo
{
    public function __construct(public PurchaseShipment $shipment)
    {
        $order = $shipment->purchaseOrder;
        $shipment->purchaseShipmentLines()->update([
            'company_id' => $order->company_id,
            'currency' => $order->currency,
            'purchase_order_id' => $order->id,
        ]);

        $orderLinesData = $order->purchaseOrderLines()
            ->get(['id', 'product_id', 'unit_price', 'contract_price'])
            ->keyBy('product_id');

        $shipment->purchaseShipmentLines()
            ->each(function (PurchaseShipmentLine $line) use ($orderLinesData) {
                $orderLine = $orderLinesData->get($line->product_id);
                if ($orderLine) {
                    $line->updateQuietly([
                        'purchase_order_line_id' => $orderLine->id,
                        'unit_price' => $orderLine->unit_price,
                        'contract_price' => $orderLine->contract_price,
                    ]);
                }
            });
    }
}
