<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find purchase orders by company
     */
    public function findByCompany(int $companyId): Collection;

    /**
     * Find purchase orders by supplier
     */
    public function findBySupplier(int $supplierId): Collection;

    /**
     * Find purchase orders by status
     */
    public function findByStatus(string $status): Collection;

    /**
     * Find purchase orders by order number
     */
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder;

    /**
     * Find purchase orders by date range
     */
    public function findByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Check if order number exists
     */
    public function orderNumberExists(string $orderNumber, ?int $excludeId = null): bool;

    /**
     * Get foreign orders
     */
    public function getForeignOrders(): Collection;

    /**
     * Get orders with pending payments
     */
    public function getOrdersWithPendingPayments(): Collection;

    /**
     * Update order totals
     */
    public function updateTotals(int $orderId): bool;

    /**
     * Update foreign status
     */
    public function updateForeignStatus(int $orderId): bool;
}