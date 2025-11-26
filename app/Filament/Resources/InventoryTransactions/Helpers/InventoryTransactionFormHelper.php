<?php

namespace App\Filament\Resources\InventoryTransactions\Helpers;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Infolists\Components as I;

/**
 * Form Helper - chỉ chứa Filament form schemas
 */
trait InventoryTransactionFormHelper
{
    /**
     * Form schema cho InventoryTransaction (nếu cần)
     */
    protected static function transactionFormSchema(): array
    {
        return [
            // Empty for now - InventoryTransaction thường read-only
        ];
    }

    /**
     * Infolist schema cho hiển thị chi tiết transaction
     */
    protected static function transactionInfolistSchema(): array
    {
        return [
            S\Group::make([
                I\TextEntry::make('product.product_code')->label('Product Code'),
                I\TextEntry::make('product.product_full_name')->label('Product'),
                I\TextEntry::make('qty'),
                I\TextEntry::make('break_price')->money('vnd'),
                I\TextEntry::make('created_at'),
            ]),
        ];
    }
}