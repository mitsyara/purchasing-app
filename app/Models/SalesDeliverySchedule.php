<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as HasBelongsToThrough;

/**
 * Kế hoạch giao hàng cho đơn bán
 */
class SalesDeliverySchedule extends Model
{
    use \App\Traits\HasLoggedActivity;
    use \App\Traits\HasCustomRecursiveQueryBuilder;
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

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            SalesDeliveryScheduleLine::class,
            'sales_delivery_schedule_id', // foreign key trên bảng trung gian
            'id', // foreign key trên bảng product (mặc định)
            'id', // local key trên bảng SalesDeliverySchedule
            'product_id' // local key trên bảng trung gian
        );
    }

    public function assortments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Assortment::class,
            SalesDeliveryScheduleLine::class,
            'sales_delivery_schedule_id', // foreign key trên bảng trung gian
            'id', // foreign key trên bảng product (mặc định)
            'id', // local key trên bảng SalesDeliverySchedule
            'assortment_id' // local key trên bảng trung gian
        );
    }

    public function deliveryLines(): HasMany
    {
        return $this->hasMany(SalesDeliveryScheduleLine::class, 'sales_delivery_schedule_id');
    }

    public function deliveryShipments(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesDeliveryScheduleShipment::class,
            'sales_delivery_schedule_shipment',
            'sales_delivery_schedule_id',
            'sales_shipment_id',
        )->withPivot([
            'id',
        ]);
    }
    public function salesShipmentDeliveries(): HasMany
    {
        return $this->hasMany(SalesDeliveryScheduleShipment::class, 'sales_delivery_schedule_id');
    }

    public function label(): Attribute
    {
        return Attribute::get(fn() => trim("{$this->salesOrder->order_number} ({$this->etd})"));
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
