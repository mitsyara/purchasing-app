<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class CallAllPurchaseShipmentServices
{
    public function __construct(public PurchaseShipment $shipment)
    {
        new SyncShipmentInfo($shipment);
        new SyncShipmentLinesInfo($shipment);
        new UpdateShipmentTotals($shipment);
    }
}
