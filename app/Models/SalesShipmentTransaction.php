<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot giữa InventoryTransaction và SalesDeliveryScheduleLine
 * - Mapping lot nào xuất cho schedule line nào với số lượng bao nhiêu
 */
class SalesShipmentTransaction extends Pivot
{
    use \App\Traits\HasLoggedActivity;

    protected $table = 'sales_shipment_transactions';

    protected $fillable = [
        'inventory_transaction_id',
        'sales_delivery_schedule_line_id',
        'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function salesDeliveryScheduleLine(): BelongsTo
    {
        return $this->belongsTo(SalesDeliveryScheduleLine::class, 'sales_delivery_schedule_line_id');
    }
}