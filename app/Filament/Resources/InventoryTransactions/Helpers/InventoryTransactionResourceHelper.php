<?php

namespace App\Filament\Resources\InventoryTransactions\Helpers;

use App\Filament\Resources\InventoryTransactions\Helpers\InventoryTransactionFormHelper;

/**
 * Resource Helper - logic hỗ trợ cho Resource
 */
trait InventoryTransactionResourceHelper
{
    use InventoryTransactionFormHelper;

    /**
     * Wrapper methods để maintain compatibility với Resource
     */
    protected static function getTransactionInfolist(): array
    {
        return static::transactionInfolistSchema();
    }

    /**
     * Business logic helpers có thể thêm vào đây
     */
}