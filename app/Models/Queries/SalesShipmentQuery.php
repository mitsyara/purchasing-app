<?php

namespace App\Models\Queries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Custom Query Builder cho SalesShipment
 */
class SalesShipmentQuery extends EloquentBuilder
{
    /**
     * Load relationships cho form edit
     */
    public function withFormRelations(): self
    {
        return $this->with([
            'deliverySchedules',
            'transactions.salesScheduleLines',
            'transactions' => function ($query) {
                $query->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export);
            }
        ]);
    }

    /**
     * Lọc theo customer
     */
    public function forCustomer(int $customerId): self
    {
        return $this->where('customer_id', $customerId);
    }

    /**
     * Lọc theo warehouse
     */
    public function fromWarehouse(int $warehouseId): self
    {
        return $this->where('warehouse_id', $warehouseId);
    }
}