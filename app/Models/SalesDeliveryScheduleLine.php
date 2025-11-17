<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

/**
 * Danh sách hàng hoá trong kế hoạch giao hàng (đơn bán)
 */
class SalesDeliveryScheduleLine extends Model
{
    use \App\Traits\HasLoggedActivity;
    use \App\Traits\HasCustomRecursiveQueryBuilder;
    use HasBelongsToThrough;

    protected $fillable = [
        'sales_delivery_schedule_id',
        'assortment_id',
        'product_id',
        'qty',
        'unit_price',
        'contract_price',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'contract_price' => 'decimal:3',
        'value' => 'decimal:6',
        'contract_value' => 'decimal:6',
    ];

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(SalesDeliverySchedule::class, 'sales_delivery_schedule_id');
    }

    public function assortment(): BelongsTo
    {
        return $this->belongsTo(Assortment::class, 'assortment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function salesOrder(): BelongsToThrough
    {
        return $this->belongsToThrough(
            SalesOrder::class,
            SalesDeliverySchedule::class,
        );
    }

    public function company(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Company::class,
            [
                SalesDeliverySchedule::class,
                SalesOrder::class,
            ]
        );
    }

    public function warehouse(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Warehouse::class,
            [
                SalesDeliverySchedule::class,
                SalesOrder::class,
            ]
        );
    }

    public function customer(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            [
                SalesDeliverySchedule::class,
                SalesOrder::class,
            ],
            foreignKeyLookup: [
                Contact::class => 'customer_id',
            ]
        );
    }

    public function inventoryTransactions(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryTransaction::class,
            'sales_shipment_transactions',
            'sales_delivery_schedule_line_id',
            'inventory_transaction_id'
        )->withPivot(['qty']);
    }
    public function scheduleDeliveries(): HasMany
    {
        return $this->hasMany(SalesShipmentTransaction::class, 'sales_delivery_schedule_line_id');
    }

    // Attributes

    /**
     * Schedule and date
     * "{$record->salesOrder->order_number} ({$record->etd})"
     */
    public function label(): Attribute
    {
        return Attribute::get(function(): string{
            $productPrefx = $this->assortment?->assortment_name ?? $this->product?->product_name ?? 'N/A';
            return "{$productPrefx} - {$this->deliverySchedule->salesOrder->order_number} ({$this->deliverySchedule->etd})";
        });
    }
}
