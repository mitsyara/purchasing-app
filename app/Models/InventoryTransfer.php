<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        'extra_costs', // json format: [{reason: string, amount: decimal}]

        'notes',
    ];

    protected $casts = [
        'transfer_status' => \App\Enums\OrderStatusEnum::class,
        'transfer_date' => 'date',
        'approved_at' => 'date',
        'extra_costs' => 'array',
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
}
