<?php

namespace App\Filament\Resources\InventoryTransfers\Helpers;

use App\Filament\Resources\InventoryTransfers\Helpers\InventoryTransferFormHelper;
use App\Models\InventoryTransaction;

trait InventoryTransferResourceHelper
{
    use InventoryTransferFormHelper;

    /**
     * Lấy danh sách lot với thông tin số dư (sử dụng Query)
     */
    protected static function getLotOptionsWithBalance(?int $warehouseId, array $excludeTransactionIds = []): array
    {
        if (!$warehouseId) return [];

        return InventoryTransaction::query()->getLotsWithBalance(null, $warehouseId, $excludeTransactionIds);
    }

    /**
     * Static method để tính tồn kho theo lot ID (sử dụng Query)
     */
    public static function getLotBalance(int $lotId): float
    {
        return InventoryTransaction::query()->calculateLotBalance($lotId);
    }

    /**
     * Tính số lượng còn lại của lot (không tính record đang edit)
     * Sử dụng logic hierarchy: Tồn = Qty gốc - SUM(tất cả export children)
     */
    protected static function calculateAvailableLotQty(int $lotId, array $excludeTransactionIds = []): float
    {
        return InventoryTransaction::query()
            ->calculateLotBalance($lotId, $excludeTransactionIds);
    }

    /**
     * Wrapper methods để maintain compatibility với Resource
     * Các method này chỉ gọi đến FormHelper
     */
    protected static function transferInfo(): array
    {
        return static::transferInfoSchema();
    }

    protected static function lotSelection(): array
    {
        return static::lotSelectionSchema();
    }
}
