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
     * Lấy danh sách lots có tồn kho với balance
     * Method chính - linh hoạt và tái sử dụng được
     * 
     * @param int|null $companyId ID công ty (có thể null)
     * @param int|null $warehouseId ID kho (có thể null)
     * @param array|string|int|null $excludeIds Danh sách transaction IDs cần loại trừ
     * @return array
     */
    public function getLotsWithBalance(?int $companyId = null, ?int $warehouseId = null, $excludeIds = null): array
    {
        // Chuẩn hóa excludeIds thành array
        $excludeTransactionIds = [];
        if ($excludeIds !== null) {
            if (is_array($excludeIds)) {
                $excludeTransactionIds = array_filter($excludeIds);
            } elseif (is_string($excludeIds) || is_int($excludeIds)) {
                $excludeTransactionIds = [$excludeIds];
            }
        }

        // Build query
        $query = $this->where('transaction_direction', InventoryTransactionDirectionEnum::Import);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lots = $query->with('product')->get();

        return $lots->mapWithKeys(function ($lot) use ($excludeTransactionIds) {
            // Tạo fresh query để tính balance chính xác
            $balance = $lot->newQuery()->calculateLotBalance($lot->id, $excludeTransactionIds);
            if ($balance <= 0) return [];

            $balanceFormatted = __number_string_converter($balance);
            $label = "{$lot->lot_fifo} | Tồn: {$balanceFormatted}";
            return [$lot->id => $label];
        })
            ->filter()
            ->toArray();
    }

    /**
     * Tính số dư của lot với exclude IDs
     * Method hỗ trợ cho getLotsWithBalance
     */
    public function calculateLotBalance(int $lotId, array $excludeTransactionIds = []): float
    {
        // Tạo fresh query để tìm lot
        $lot = $this->getModel()->newQuery()
            ->where('transaction_direction', InventoryTransactionDirectionEnum::Import)
            ->find($lotId);

        if (!$lot) return 0;

        $originalQty = $lot->qty ?? 0;

        // Tạo fresh query để tính exported qty
        $exportQuery = $this->getModel()->newQuery()
            ->where('parent_id', $lotId)
            ->where('transaction_direction', InventoryTransactionDirectionEnum::Export);

        // Loại trừ các transaction IDs cụ thể
        if (!empty($excludeTransactionIds)) {
            $exportQuery->whereNotIn('id', $excludeTransactionIds);
        }

        $exportedQty = $exportQuery->sum('qty');
        return max(0, $originalQty - $exportedQty);
    }

    /**
     * Kiểm tra tồn kho available
     */
    public function checkAvailability(int $transactionId, float $requestedQty, array $excludeTransactionIds = []): bool
    {
        $balance = $this->calculateLotBalance($transactionId, $excludeTransactionIds);
        return $balance >= $requestedQty;
    }

    /**
     * Lọc theo danh sách product IDs
     * 
     * @param array|int $productIds ID hoặc array IDs của products
     * @return static
     */
    public function forProducts($productIds): static
    {
        if (is_array($productIds)) {
            return $this->whereIn('product_id', $productIds);
        }
        
        return $this->where('product_id', $productIds);
    }

    /**
     * Lọc theo kho hàng
     * 
     * @param int $warehouseId ID của kho hàng
     * @return static
     */
    public function inWarehouse(int $warehouseId): static
    {
        return $this->where('warehouse_id', $warehouseId);
    }

    /**
     * Lọc các giao dịch có thể export (Import transactions với balance > 0)
     * 
     * @return static
     */
    public function availableForExport(): static
    {
        return $this->where('transaction_direction', InventoryTransactionDirectionEnum::Import)
            ->whereRaw('
                (SELECT COALESCE(SUM(children.qty), 0) 
                 FROM inventory_transactions children 
                 WHERE children.parent_id = inventory_transactions.id
                   AND children.transaction_direction = ?) < inventory_transactions.qty
            ', [InventoryTransactionDirectionEnum::Export->value]);
    }

    /**
     * Lấy danh sách lots có tồn kho với balance cho SalesShipment
     * Sử dụng lot_description thay vì lot_fifo và "Còn:" thay vì "Tồn:"
     * 
     * @param int|null $companyId ID công ty (có thể null)
     * @param int|null $warehouseId ID kho (có thể null)
     * @param array|string|int|null $excludeIds Danh sách transaction IDs cần loại trừ
     * @param bool $includeZeroBalance Có bao gồm lots có balance = 0 không
     * @return array
     */
    public function getLotsWithBalanceForShipment(
        ?int $companyId = null, 
        ?int $warehouseId = null, 
        $excludeIds = null,
        bool $includeZeroBalance = false
    ): array {
        // Chuẩn hóa excludeIds thành array
        $excludeTransactionIds = [];
        if ($excludeIds !== null) {
            if (is_array($excludeIds)) {
                $excludeTransactionIds = array_filter($excludeIds);
            } elseif (is_string($excludeIds) || is_int($excludeIds)) {
                $excludeTransactionIds = [$excludeIds];
            }
        }

        // Build query
        $query = $this->where('transaction_direction', InventoryTransactionDirectionEnum::Import);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lots = $query->with('product')->get();

        return $lots->mapWithKeys(function ($lot) use ($excludeTransactionIds, $includeZeroBalance) {
            // Tạo fresh query để tính balance chính xác
            $balance = $lot->newQuery()->calculateLotBalance($lot->id, $excludeTransactionIds);
            if (!$includeZeroBalance && $balance <= 0) return [];

            $balanceFormatted = __number_string_converter($balance);
            $label = "{$lot->lot_description} | Còn: {$balanceFormatted}";
            return [$lot->id => $label];
        })
            ->filter()
            ->toArray();
    }
}
