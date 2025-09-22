<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class CallAllServices
{
    public function __construct(public PurchaseShipment $shipment)
    {
        new SyncShipmentInfo($shipment);
        new SyncShipmentLinesInfo($shipment);
        new UpdateShipmentTotals($shipment);
    }
}
