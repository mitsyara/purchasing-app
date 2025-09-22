<?php

namespace App\Models\Queries;

use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderQuery extends Builder
{
    public function incompleteShipmentsQty(): static
    {
        return $this
            ->withSum('purchaseOrderLines as qty', 'purchase_order_lines.qty')
            ->withSum('purchaseShipmentLines as total_shipments_qty', 'purchase_shipment_lines.qty')
            ->havingRaw('total_shipments_qty < qty OR total_shipments_qty IS NULL');
    }
}
