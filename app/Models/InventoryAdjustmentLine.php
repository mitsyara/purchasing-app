<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

class InventoryAdjustmentLine extends Model
{
    use HasBelongsToThrough;
    use \App\Traits\HasInventoryTransactions;

    protected $fillable = [
        'inventory_adjustment_id',
        'product_id',
        'parent_transaction_id', // Cho OUT adjustments
        'lot_no',
        'mfg_date',
        'exp_date',
        'adjustment_qty',
        'io_price', // default: VND
    ];

    protected $casts = [
        'mfg_date' => 'date',
        'exp_date' => 'date',

        'adjustment_qty' => 'decimal:3',
        'io_price' => 'decimal:3',
    ];

    public function inventoryAdjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function company(): BelongsToThrough
    {
        return $this->belongsToThrough(Company::class, InventoryAdjustment::class);
    }

    public function warehouse(): BelongsToThrough
    {
        return $this->belongsToThrough(Warehouse::class, InventoryAdjustment::class);
    }

    /**
     * Parent transaction cho OUT adjustments
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'parent_transaction_id');
    }
}
