<?php

namespace App\Services\InventoryLine;

use App\Models\InventoryTransaction;

class SyncInfoToDescendants
{
    /**
     * Create a new class instance.
     */
    public function __construct(InventoryTransaction $transaction)
    {
        if (!$transaction->descendants()->exists()) return;

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
}
