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
 * Kế hoạch giao hàng cho đơn bán
 */
class SalesDeliverySchedule extends Model
{
    use \App\Traits\HasLoggedActivity;
    use HasBelongsToThrough;

    protected $fillable = [
        'sales_order_id',
        'delivery_status',
        'export_warehouse_id',
        'from_date',
        'to_date',
        'delivery_address',
        'notes',
    ];

    protected $casts = [
        'delivery_status' => \App\Enums\DeliveryStatusEnum::class,
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function company(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Company::class,
            SalesOrder::class,
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'export_warehouse_id');
    }

    public function customer(): BelongsToThrough
    {
        return $this->belongsToThrough(
            Contact::class,
            SalesOrder::class,
            foreignKeyLookup: [
                Contact::class => 'customer_id',
            ]
        );
    }

    public function deliveryLines(): HasMany
    {
        return $this->hasMany(SalesDeliveryScheduleLine::class, 'sales_delivery_schedule_id');
    }

    public function deliveryShipments(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesDeliveryShipment::class,
            'sales_delivery_schedule_shipment',
            'sales_delivery_schedule_id',
            'sales_shipment_id',
        )->withPivot([
            'id',
        ]);
    }
    public function salesShipmentDeliveries(): HasMany
    {
        return $this->hasMany(SalesDeliveryShipment::class, 'sales_delivery_schedule_id');
    }

    public function etd(): Attribute
    {
        return Attribute::get(function () {
            if ($this->from_date && $this->to_date && $this->from_date != $this->to_date) {
                return $this->from_date->format('d/m/Y') . '-' . $this->to_date->format('d/m/Y');
            } elseif ($this->from_date) {
                return $this->from_date->format('d/m/Y');
            } elseif ($this->to_date) {
                return $this->to_date->format('d/m/Y');
            } else {
                return null;
            }
        });
    }

    public function productList(): Attribute
    {
        return Attribute::get(
            fn() => $this->deliveryLines
                ->map(fn($line) => ($line->product?->product_name ?? $line->assortment?->assortment_name)
                    . ' : ' . __number_string_converter($line->qty))
        );
    }
}
