<?php

namespace App\Models;

use App\Observers\PurchaseOrderLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PurchaseOrderLineObserver::class])]
class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'company_id',
        'warehouse_id',
        'currency',

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
        'unit_price' => 'decimal:2',
        'contract_price' => 'decimal:2',
        'extra_cost' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
