<?php

namespace App\Models\Queries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Custom Query Builder cho SalesDeliveryScheduleLine
 */
class SalesDeliveryScheduleLineQuery extends EloquentBuilder
{
    /**
     * Lọc theo delivery schedule IDs
     */
    public function inSchedules(array $scheduleIds): self
    {
        return $this->whereIn('sales_delivery_schedule_id', array_filter($scheduleIds));
    }

    /**
     * Lấy options cho form select với label đầy đủ
     */
    public function getFormOptions(array $scheduleIds): array
    {
        return $this->inSchedules($scheduleIds)
            ->with(['deliverySchedule.salesOrder', 'product', 'assortment'])
            ->get()
            ->pluck('label', 'id')
            ->toArray();
    }

    /**
     * Lấy options với remaining quantity hiển thị trong label
     */
    public function getFormOptionsWithRemaining(array $scheduleIds, ?int $excludeShipmentId = null): array
    {
        return $this->inSchedules($scheduleIds)
            ->with(['deliverySchedule.salesOrder', 'product', 'assortment'])
            ->get()
            ->mapWithKeys(function ($scheduleLine) use ($excludeShipmentId) {
                // Tính tổng đã xuất (loại trừ shipment đang edit)
                $shippedQty = DB::table('sales_shipment_transactions as sst')
                    ->join('inventory_transactions as it', 'sst.inventory_transaction_id', '=', 'it.id')
                    ->where('sst.sales_delivery_schedule_line_id', $scheduleLine->id)
                    ->when($excludeShipmentId, function ($query) use ($excludeShipmentId) {
                        $query->where('it.sourceable_id', '!=', $excludeShipmentId)
                              ->where('it.sourceable_type', \App\Models\SalesShipment::class);
                    })
                    ->sum('sst.qty');

                $remainingQty = $scheduleLine->qty - ($shippedQty ?: 0);
                $label = $scheduleLine->label . " (Cần: " . __number_string_converter($remainingQty) . ")";
                
                return [$scheduleLine->id => $label];
            })
            ->toArray();
    }

    /**
     * Lấy product IDs từ schedule line (bao gồm assortment products)
     */
    public function getProductIds(int $scheduleLineId): array
    {
        $scheduleLine = $this->with(['assortment.products'])
            ->find($scheduleLineId);

        if (!$scheduleLine) {
            return [];
        }

        // Nếu là assortment thì lấy tất cả products trong assortment
        if ($scheduleLine->assortment_id) {
            return $scheduleLine->assortment->products()->pluck('products.id')->toArray();
        }

        // Nếu là single product
        return $scheduleLine->product_id ? [$scheduleLine->product_id] : [];
    }
}