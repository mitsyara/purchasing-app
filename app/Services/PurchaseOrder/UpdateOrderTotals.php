<?php

namespace App\Services\PurchaseOrder;

use App\Models\PurchaseOrder;

class UpdateOrderTotals
{
    public function __construct(public PurchaseOrder $order)
    {
        // Calculate Totals
        $totalValue = $order->purchaseOrderLines()->sum('value');
        $totalContractValue = $order->purchaseOrderLines()->sum('contract_value');
        $totalExtraCost = $order->purchaseOrderLines()->sum('extra_cost');

        $order->updateQuietly([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
        ]);

        // Calculate Foreign
        $isForeign = $order->company->country_id !== $order->supplier->country_id;
        $order->updateQuietly([
            'is_foreign' => $isForeign,
        ]);
    }
}
