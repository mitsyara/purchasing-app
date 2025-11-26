<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

class InventoryTransferLine extends Model
{
    use HasBelongsToThrough;
    use \App\Traits\HasInventoryTransactions;

    protected $fillable = [
        'inventory_transfer_id',
        'lot_id',
        'transfer_qty',
        'extra_cost', // default: VND
    ];

    // Relationships

    public function company(): BelongsToThrough
    {
        return  $this->belongsToThrough(
            Company::class,
            InventoryTransfer::class,
        );
    }

    public function fromWarehouse(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Warehouse::class,
            InventoryTransfer::class,
        );
    }

    public function toWarehouse(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Warehouse::class,
            InventoryTransfer::class,
        );
    }

    public function approvedBy(): BelongsToThrough
    {
        return $this->belongsToThrough(
            User::class,
            InventoryTransfer::class,
            foreignKeyLookup: [
                User::class => 'approved_by',
            ]
        );
    }

    public function inventoryTransfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'lot_id');
    }
}
