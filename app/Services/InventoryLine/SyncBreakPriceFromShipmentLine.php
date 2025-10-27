<?php

namespace App\Services\InventoryLine;

use App\Models\PurchaseShipmentLine;

class SyncBreakPriceFromShipmentLine
{
    /**
     * Create a new class instance.
     */
    public function __construct(PurchaseShipmentLine $shipmentLine)
    {
        $shipmentLine->transactions()->update([
            'break_price' => $shipmentLine->break_price,
        ]);
    }
}
