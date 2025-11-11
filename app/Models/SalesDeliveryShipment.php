<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SalesDeliveryShipment extends Pivot
{
    use \App\Traits\HasLoggedActivity;

    protected $table = 'sales_delivery_schedule_shipment';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'sales_shipment_id',
        'sales_delivery_schedule_id',
    ];

    public function salesShipment(): BelongsTo
    {
        return $this->belongsTo(SalesShipment::class, 'sales_shipment_id');
    }

    public function salesDeliverySchedule(): BelongsTo
    {
        return $this->belongsTo(SalesDeliverySchedule::class, 'sales_delivery_schedule_id');
    }
}
