<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as TraitsBelongsToThrough;

/**
 * Danh sách hàng hoá trong kế hoạch giao hàng (đơn bán)
 */
class SalesDeliveryScheduleLine extends Model
{
    use \App\Traits\HasLoggedActivity;
    use TraitsBelongsToThrough;

    protected $fillable = [
        'sales_delivery_schedule_id',
        'assortment_id',
        'product_id',
        'qty',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
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
}
