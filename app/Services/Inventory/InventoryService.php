<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use App\Models\PurchaseShipmentLine;
use App\Enums\InventoryTransactionTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Inventory
 */
class InventoryService
{
    /**
     * Đồng bộ thông tin đến các giao dịch con
     */
    public function syncInfoToDescendants(InventoryTransaction $transaction): void
    {
        if (!$transaction->descendants()->exists()) {
            return;
        }

        $transaction->descendants()->update([
            'company_id' => $transaction->company_id,
            'warehouse_id' => $transaction->warehouse_id,
            'product_id' => $transaction->product_id,
            'lot_no' => $transaction->lot_no,
            'mfg_date' => $transaction->mfg_date,
            'exp_date' => $transaction->exp_date,
            'break_price' => $transaction->break_price,
        ]);
    }

    /**
     * Đồng bộ dữ liệu giao dịch từ shipment line
     */
    public function syncFromShipmentLine(PurchaseShipmentLine $shipmentLine): void
    {
        $shipment = $shipmentLine->purchaseShipment;

        $shipmentLine->transactions()->update([
            'company_id' => $shipment->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $shipmentLine->product_id,
            'transaction_type' => InventoryTransactionTypeEnum::Import->value,
            'io_price' => $shipmentLine->unit_price,
            'io_currency' => $shipmentLine->currency,
            'break_price' => $shipmentLine->break_price,
        ]);
    }
}