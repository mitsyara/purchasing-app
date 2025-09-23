<?php

namespace App\Services\PurchaseShipment;

use App\Enums\ShipmentStatusEnum;
use App\Models\PurchaseShipment;

class MarkShipmentDelivered
{
    public function __construct(public PurchaseShipment $shipment)
    {
        if (in_array($shipment->shipment_status, [
            ShipmentStatusEnum::Cancelled,
            ShipmentStatusEnum::Delivered,
        ])) {
            throw new \InvalidArgumentException("Cannot mark shipment as arrived. Current status: {$shipment->shipment_status->value}");
        }

        $shipment->update([
            'shipment_status' => ShipmentStatusEnum::Delivered,
        ]);
    }
}
