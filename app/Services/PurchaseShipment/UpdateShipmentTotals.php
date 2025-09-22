<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;

class UpdateShipmentTotals
{
    public function __construct(public PurchaseShipment $shipment)
    {
        // TODO: calculate shipment totals
        $totalValue = $shipment->purchaseShipmentLines()->sum('value');
        $totalContractValue = $shipment->purchaseShipmentLines()->sum('contract_value');
        $totalExtraCost = collect($shipment->extra_costs)->sum() ?? 0;

        $totalQty = $shipment->purchaseShipmentLines()->sum('qty');
        if ($totalQty > 0) {
            $averageCost = $totalExtraCost / ($totalQty ?? 1);
        }

        $shipment->update([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
            'average_cost' => $averageCost ?? null,
        ]);
    }
}
