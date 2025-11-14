<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Znck\Eloquent\Relations\BelongsToThrough;

/**
 * Lần giao hàng thực tế (Đơn bán)
 * - Có thể trỏ về nhiều kế hoạch giao hàng (SalesDeliverySchedule)
 * - Mỗi lần giao hàng có thể có nhiều lot/batch hàng (InventoryTransaction)
 */
class SalesShipment extends Model
{
    use \App\Traits\HasLoggedActivity;
    use \App\Traits\HasInventoryTransactions;

    protected $fillable = [
        'customer_id',
        'warehouse_id',

        'shipment_no',
        'shipment_status',
        'atd',
        'tracking_number',
        'delivery_carrier',
        'delivery_staff',

        'billing_address',
        'shipping_address',

        'notes',
    ];

    protected $casts = [
        'atd' => 'date',
        'shipment_status' => \App\Enums\ShipmentStatusEnum::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function deliverySchedules(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesDeliverySchedule::class,
            'sales_delivery_schedule_shipment',
            'sales_shipment_id',
            'sales_delivery_schedule_id'
        )->withPivot([
            'id',
        ]);
    }
    public function salesShipmentDeliveries(): HasMany
    {
        return $this->hasMany(SalesDeliveryScheduleShipment::class, 'sales_shipment_id');
    }
}
