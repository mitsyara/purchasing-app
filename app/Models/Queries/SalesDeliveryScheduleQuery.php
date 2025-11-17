<?php

namespace App\Models\Queries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Custom Query Builder cho SalesDeliverySchedule
 */
class SalesDeliveryScheduleQuery extends EloquentBuilder
{
    /**
     * Lọc theo customer và warehouse cho shipment form
     */
    public function forShipmentForm(?int $customerId, ?int $warehouseId): self
    {
        $query = $this->with(['salesOrder', 'deliveryLines']);

        if ($customerId) {
            $query->whereHas('salesOrder', fn($q) => $q->where('customer_id', $customerId));
        }

        if ($warehouseId) {
            $query->where('export_warehouse_id', $warehouseId);
        }

        return $query;
    }

    /**
     * Lấy unique delivery addresses từ selected schedules
     */
    public function getDeliveryAddresses(array $scheduleIds): array
    {
        return $this->whereIn('id', array_filter($scheduleIds))
            ->pluck('delivery_address')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }
}