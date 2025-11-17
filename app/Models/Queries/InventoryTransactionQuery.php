<?php

namespace App\Models\Queries;

use App\Enums\InventoryTransactionDirectionEnum;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder as RecursiveBuilder;

/**
 * Custom Query Builder cho InventoryTransaction
 */
class InventoryTransactionQuery extends RecursiveBuilder
{
    /**
     * Lấy các lot nhập kho có thể xuất (còn tồn)
     */
    public function availableForExport(): self
    {
        return $this->where('transaction_direction', InventoryTransactionDirectionEnum::Import)
            ->withSum('children as shipped_qty', 'qty')
            ->havingRaw('qty - COALESCE(shipped_qty, 0) > 0');
    }

    /**
     * Lọc theo warehouse
     */
    public function inWarehouse(int $warehouseId): self
    {
        return $this->where('warehouse_id', $warehouseId);
    }

    /**
     * Lọc theo danh sách product IDs
     */
    public function forProducts(array $productIds): self
    {
        return $this->whereIn('product_id', array_filter($productIds));
    }

    /**
     * Lấy available lots cho form options
     */
    public function getAvailableLotsForForm(array $productIds, int $warehouseId): array
    {
        return $this->forProducts($productIds)
            ->inWarehouse($warehouseId)
            ->availableForExport()
            ->with(['product'])
            ->get()
            ->pluck('lot_description', 'id')
            ->toArray();
    }

    /**
     * Lấy available lots với remaining quantity trong label
     */
    public function getAvailableLotsForFormWithRemaining(array $productIds, int $warehouseId, array $excludeTransactionIds = []): array
    {
        return $this->forProducts($productIds)
            ->inWarehouse($warehouseId)
            ->availableForExport()
            ->with(['product'])
            ->get()
            ->mapWithKeys(function ($transaction) use ($excludeTransactionIds) {
                // Build description parts
                $parts = [];
                $parts[] = $transaction->product->product_code ?? 'N/A';
                
                if ($transaction->lot_no) {
                    $parts[] = "{$transaction->lot_no}";
                }
                
                if ($transaction->mfg_date) {
                    $parts[] = "{$transaction->mfg_date->format('d/m/Y')}";
                }
                
                if ($transaction->exp_date) {
                    $parts[] = "{$transaction->exp_date->format('d/m/Y')}";
                }

                // Tính remaining quantity (loại trừ transactions đang edit)
                $shippedQty = $transaction->children()
                    ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export)
                    ->when(!empty($excludeTransactionIds), function ($query) use ($excludeTransactionIds) {
                        $query->whereNotIn('id', $excludeTransactionIds);
                    })
                    ->sum('qty');

                $remainingQty = $transaction->qty - ($shippedQty ?: 0);
                $parts[] = "Còn: " . __number_string_converter($remainingQty);
                
                $description = implode(' | ', $parts);
                
                return [$transaction->id => $description];
            })
            ->toArray();
    }

    /**
     * Kiểm tra tồn kho available
     */
    public function checkAvailability(int $transactionId, float $requestedQty): bool
    {
        $transaction = $this->withSum('children as shipped_qty', 'qty')
            ->find($transactionId);

        if (!$transaction) {
            return false;
        }

        $availableQty = $transaction->qty - ($transaction->shipped_qty ?: 0);
        return $availableQty >= $requestedQty;
    }
}