<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    use \App\Traits\HasLoggedActivity;

    protected $fillable = [
        'sales_order_id',
        'assortment_id',
        'product_id',
        'qty',
        'unit_price',
        'contract_price',
        'extra_cost',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'contract_price' => 'decimal:3',
        'extra_cost' => 'decimal:3',
        'value' => 'decimal:6',
        'contract_value' => 'decimal:6',
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function assortment(): BelongsTo
    {
        return $this->belongsTo(Assortment::class, 'assortment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
