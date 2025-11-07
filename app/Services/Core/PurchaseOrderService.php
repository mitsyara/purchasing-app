<?php

namespace App\Services\Core;

use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;
use App\Helpers\OrderNumberGenerator;

class PurchaseOrderService
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {}

    /**
     * Create a new purchase order
     */
    public function create(array $data): PurchaseOrder
    {
        // Set created_by if authenticated
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return $this->purchaseOrderRepository->create($data);
    }

    /**
     * Update purchase order
     */
    public function update(int $id, array $data): bool
    {
        // Set updated_by if authenticated
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return $this->purchaseOrderRepository->update($id, $data);
    }

    /**
     * Process order (change status to In Progress)
     */
    public function processOrder(int $orderId, array $data): bool
    {
        $this->validateOrderData($data);

        $order = $this->purchaseOrderRepository->findOrFail($orderId);

        $supplierCode = $order->supplier->contact_short_name
            ?? $order->supplier->contact_code
            ?? 'N/A';

        return $this->purchaseOrderRepository->update($orderId, [
            'order_status' => \App\Enums\OrderStatusEnum::Inprogress,
            'order_number' => $data['order_number'],
            'order_date' => $data['order_date'],
            'order_description' => $data['order_date'] . ' ' . $data['order_number'] . ' [' . $supplierCode . ']',
        ]);
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId): bool
    {
        return $this->purchaseOrderRepository->update($orderId, [
            'order_status' => \App\Enums\OrderStatusEnum::Canceled,
        ]);
    }

    /**
     * Validate order data
     */
    public function validateOrderData(array $data, ?string $format = 'Y-m-d'): void
    {
        if (!$data['order_number'] || !$data['order_date']) {
            throw new \Exception('Order number and order date are required.');
        }

        $date = \Carbon\Carbon::createFromFormat($format, $data['order_date']);

        if (!$date || $date->format($format) !== $data['order_date']) {
            throw new \Exception('Invalid order date format. Expected format: ' . $format);
        }
    }

    /**
     * Generate order number
     */
    public function generateOrderNumber(?array $data = null, ?int $orderId = null): string
    {
        if ($data) {
            $companyId = $data['id'] ?? null;
            $orderDate = $data['order_date'] ?? null;
        } else if ($orderId) {
            $order = $this->purchaseOrderRepository->findOrFail($orderId);
            $companyId = $order->company_id;
            $orderDate = $order->order_date;
        } else {
            throw new \InvalidArgumentException('Either data or order ID must be provided');
        }

        $baseNumber = OrderNumberGenerator::generatePurchaseOrderNumber($companyId, $orderDate);

        return OrderNumberGenerator::makeUnique(
            $baseNumber,
            PurchaseOrder::class,
            'order_number',
            $orderId
        );
    }

    /**
     * Sync order information (call all services)
     */
    public function syncOrderInfo(int $orderId): void
    {
        $order = $this->purchaseOrderRepository->findOrFail($orderId);

        // Sync order lines info
        $this->syncOrderLinesInfo($orderId);

        // Sync shipments info
        $this->syncShipmentsInfo($orderId);

        // Update totals
        $this->updateTotals($orderId);

        // Update foreign status
        $this->updateForeignStatus($orderId);

        // TODO: calculate order's received / paid values
    }

    /**
     * Sync order lines information
     */
    public function syncOrderLinesInfo(int $orderId): void
    {
        $order = $this->purchaseOrderRepository->findOrFail($orderId);

        $order->purchaseOrderLines()->update([
            'company_id' => $order->company_id ?? null,
            'warehouse_id' => $order->warehouse_id ?? null,
            'currency' => $order->currency ?? null,
        ]);
    }

    /**
     * Sync shipments information
     */
    public function syncShipmentsInfo(int $orderId): void
    {
        $order = $this->purchaseOrderRepository->findOrFail($orderId);

        $order->purchaseShipments()->update([
            'company_id' => $order->company_id,
            'currency' => $order->currency,
            'staff_buy_id' => $order->staff_buy_id,
            'supplier_id' => $order->supplier_id,
            'supplier_contract_id' => $order->supplier_contract_id,
            'supplier_payment_id' => $order->supplier_payment_id,
            'staff_sales_id' => $order->staff_sales_id,
            'end_user_id' => $order->end_user_id,
        ]);
    }

    /**
     * Update order totals
     */
    public function updateTotals(int $orderId): bool
    {
        return $this->purchaseOrderRepository->updateTotals($orderId);
    }

    /**
     * Update foreign status
     */
    public function updateForeignStatus(int $orderId): bool
    {
        return $this->purchaseOrderRepository->updateForeignStatus($orderId);
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus(string $status)
    {
        return $this->purchaseOrderRepository->findByStatus($status);
    }

    /**
     * Get orders by supplier
     */
    public function getOrdersBySupplier(int $supplierId)
    {
        return $this->purchaseOrderRepository->findBySupplier($supplierId);
    }

    /**
     * Get orders by company
     */
    public function getOrdersByCompany(int $companyId)
    {
        return $this->purchaseOrderRepository->findByCompany($companyId);
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder
    {
        return $this->purchaseOrderRepository->findByOrderNumber($orderNumber);
    }

    /**
     * Update order totals (from UpdateOrderTotals)
     */
    public function updateOrderInfo(int $orderId): void
    {
        $order = $this->purchaseOrderRepository->find($orderId);

        if (!$order) return;

        // Calculate Totals
        $totalValue = $order->purchaseOrderLines()->sum('value');
        $totalContractValue = $order->purchaseOrderLines()->sum('contract_value');
        $totalExtraCost = $order->purchaseOrderLines()->sum('extra_cost');

        $order->updateQuietly([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
        ]);

        // Calculate Foreign
        $isForeign = $order->company->country_id !== $order->supplier->country_id;
        $order->updateQuietly([
            'is_foreign' => $isForeign,
        ]);
    }
}
