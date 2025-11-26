<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransfer extends Model
{
    //
    protected $fillable = [
        'company_id',
        'from_warehouse_id',
        'to_warehouse_id',

        'transfer_status',
        'transfer_date',
        'created_by',
        'approved_by',
        'approved_at',

        // json format: [{reason: string, amount: decimal}]
        'extra_costs',
        'total_extra_cost', // default VND
        'average_extra_cost_per_unit',

        'notes',
    ];

    protected $casts = [
        'transfer_status' => \App\Enums\OrderStatusEnum::class,
        'transfer_date' => 'date',
        'approved_at' => 'date',
        'extra_costs' => 'array',
        'total_extra_cost' => 'decimal:2',
        'average_extra_cost_per_unit' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transferLines(): HasMany
    {
        return $this->hasMany(InventoryTransferLine::class, 'inventory_transfer_id');
    }

    /**
     * Mutator để đảm bảo extra_costs có đúng type
     */
    public function setExtraCostsAttribute($value): void
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (isset($item['amount'])) {
                    $item['amount'] = (float) $item['amount'];
                }
                return $item;
            }, $value);
        }
        $this->attributes['extra_costs'] = json_encode($value);
    }

    /**
     * Calculate total extra cost
     */
    public function calculateTotalExtraCost(): void
    {
        $total = 0;
        $extraCosts = $this->extra_costs ?? [];
        foreach ($extraCosts as $cost) {
            $total += $cost['amount'] ?? 0;
        }
        $this->update([
            'total_extra_cost' => $total,
        ]);
    }

    public function calculateAvgExtraCost(): void
    {
        $totalUnits = $this->transferLines()->sum('transfer_qty');
        $avgCost = $totalUnits > 0 ? $this->total_extra_cost / $totalUnits : 0;
        $this->update([
            'average_extra_cost_per_unit' => $avgCost,
        ]);
    }
}
