<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class UpdateShipmentTotals
{
    public function __construct(public PurchaseShipment $shipment) {
        // TODO: calculate shipment totals
    }
}
