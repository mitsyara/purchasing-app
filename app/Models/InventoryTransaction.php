<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',

        'sourceable_id',
        'sourceable_type',

        'transaction_type',
        'transaction_date',
        'qty',
        'lot_no',
        'mfg_date',
        'exp_date',

        'is_checked',
        'checked_by',
        'notes',
    ];

    protected $casts = [
        'transaction_type' => \App\Enums\InventoryTransactionTypeEnum::class,
        'transaction_date' => 'date',
        'mfg_date' => 'date',
        'exp_date' => 'date',

        'qty' => 'decimal:3',
        'is_checked' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
