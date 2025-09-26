<?php

namespace App\Services\InventoryLine;

use App\Models\PurchaseShipmentLine;

class SyncFromShipmentLine
{
    public function __construct(PurchaseShipmentLine $shipmentLine)
    {
        $shipment = $shipmentLine->purchaseShipment;

        $shipmentLine->transactions()->update([
            'company_id' => $shipment->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $shipmentLine->product_id,
            'transaction_type' => \App\Enums\InventoryTransactionTypeEnum::Import->value,

            'import_price' => $shipmentLine->break_price,
        ]);
    }
}