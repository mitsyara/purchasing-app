<?php

namespace App\Models;

use App\Traits\HasInventoryTransactions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseShipmentLine extends Model
{
    use HasInventoryTransactions;
    use \App\Traits\HasLoggedActivity;

    protected $fillable = [
        'purchase_shipment_id',
        'purchase_order_id',
        'purchase_order_line_id',

        'company_id',
        'warehouse_id',

        'assortment_id',
        'product_id',
        'qty',
        'unit_price',
        'contract_price',
        'break_price',

        'average_cost',
        'currency',
        'is_ready',
    ];

    protected $casts = [
        'unit_price' => 'decimal:3',
        'contract_price' => 'decimal:3',
        'average_cost' => 'decimal:3',
        'break_price' => 'decimal:3',
        'qty' => 'decimal:3',

        'is_ready' => 'boolean',
    ];

    // Model Relations

    public function purchaseShipment(): BelongsTo
    {
        return $this->belongsTo(PurchaseShipment::class, 'purchase_shipment_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
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

    // Helpers

    public function getFormatedUnitPrice(): string
    {
        return \Illuminate\Support\Number::currency($this->unit_price, $this->currency, app()->getLocale());
    }
}
