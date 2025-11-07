<?php

namespace App\Repositories;

use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * Get the model class name
     */
    protected function getModelClass(): string
    {
        return PurchaseOrder::class;
    }

    /**
     * Find purchase orders by company
     */
    public function findByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    /**
     * Find purchase orders by supplier
     */
    public function findBySupplier(int $supplierId): Collection
    {
        return $this->model->where('supplier_id', $supplierId)->get();
    }

    /**
     * Find purchase orders by status
     */
    public function findByStatus(string $status): Collection
    {
        return $this->model->where('order_status', $status)->get();
    }

    /**
     * Find purchase orders by order number
     */
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    /**
     * Find purchase orders by date range
     */
    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('order_date', [$startDate, $endDate])->get();
    }

    /**
     * Check if order number exists
     */
    public function orderNumberExists(string $orderNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->where('order_number', $orderNumber);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get foreign orders
     */
    public function getForeignOrders(): Collection
    {
        return $this->model->where('is_foreign', true)->get();
    }

    /**
     * Get orders with pending payments
     */
    public function getOrdersWithPendingPayments(): Collection
    {
        return $this->model->where('total_value', '>', 'total_paid_value')->get();
    }

    /**
     * Update order totals
     */
    public function updateTotals(int $orderId): bool
    {
        $order = $this->findOrFail($orderId);
        
        $totalValue = $order->purchaseOrderLines()->sum('value');
        $totalContractValue = $order->purchaseOrderLines()->sum('contract_value');
        $totalExtraCost = $order->purchaseOrderLines()->sum('extra_cost');

        return $order->updateQuietly([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
        ]);
    }

    /**
     * Update foreign status
     */
    public function updateForeignStatus(int $orderId): bool
    {
        $order = $this->findOrFail($orderId);
        
        $isForeign = $order->company->country_id !== $order->supplier->country_id;
        
        return $order->updateQuietly([
            'is_foreign' => $isForeign,
        ]);
    }

    /**
     * Find orders with eager loaded relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?PurchaseOrder
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Get orders with specific includes
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Update order quietly (without triggering events)
     */
    public function updateQuietly(int $id, array $data): bool
    {
        $record = $this->findOrFail($id);
        return $record->updateQuietly($data);
    }
}